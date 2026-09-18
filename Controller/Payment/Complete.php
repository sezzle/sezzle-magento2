<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Controller\Payment;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Sezzle\Sezzlepay\Controller\AbstractController\Sezzle;

/**
 * Class Complete
 * @package Sezzle\Sezzlepay\Controller\Payment
 */
class Complete extends Sezzle
{
    private const SUCCESS_PATH = 'checkout/onepage/success';

    /**
     * Complete the order
     */
    public function execute()
    {
        $redirectPath = 'checkout/cart';
        $quote = null;
        try {
            $quote = $this->checkoutSession->getQuote();
            $this->helper->logSezzleActions("Returned from Sezzle.");

            // Idempotency guard. This endpoint can be hit more than once for the same
            // quote (Sezzle redirect + browser refresh, a double-click, or two concurrent
            // requests). Re-submitting reuses the quote's sticky reserved increment ID and
            // fails with "Unique constraint violation found", which leaves the shopper with
            // an authorized-but-uncaptured Sezzle order and no Magento order. If the order
            // already exists for this quote, send the shopper straight to the success page.
            if ($order = $this->orderRecovery->getExistingOrder($quote)) {
                $this->helper->logSezzleActions([
                    'log_origin' => __METHOD__,
                    'message' => 'Order already placed for this quote; skipping resubmission',
                    'reserved_order_id' => $quote->getReservedOrderId(),
                    'store_id' => $quote->getStoreId(),
                    'order_id' => $order->getId()
                ]);
                $this->restoreCheckoutSession($quote, $order);

                return $this->resultRedirectFactory->create()->setPath(self::SUCCESS_PATH);
            }

            if ($customerUUID = $this->request->getParam('customer-uuid')) {
                $this->helper->logSezzleActions("****Start Tokenize record save****");
                $this->helper->logSezzleActions("Customer UUID : $customerUUID");
                $this->tokenize->saveTokenizeRecord($quote);
                $this->helper->logSezzleActions("****Start Tokenize record end****");
            }

            $orderId = $this->placeOrder($quote);
            if (!$orderId) {
                throw new CouldNotSaveException(__("Unable to place the order."));
            }
            $redirectPath = self::SUCCESS_PATH;
        } catch (\Throwable $e) {
            // Catch broadly, by design. Magento surfaces the same underlying failure as several
            // different exception types, including a plain \Exception when something throws
            // while submitQuote() is rolling back a failed submit. Matching on specific classes
            // let that case escape the controller entirely, which showed the shopper a raw
            // Magento error report page and left their Sezzle authorization stranded. By this
            // point the shopper may already be authorized, so any failure needs handling.
            $this->logPlacementFailure(__METHOD__, $e, $quote);

            // Last-resort recovery: the failure may have been a reserved-id collision while
            // the order was in fact placed for this quote by a concurrent request. If so,
            // land the shopper on success instead of showing a raw error.
            if ($order = $this->findRecoverableOrder($quote)) {
                $this->helper->logSezzleActions([
                    'log_origin' => __METHOD__,
                    'message' => 'Recovered already-placed order after exception',
                    'order_id' => $order->getId(),
                    'error' => $e->getMessage()
                ]);
                $this->restoreCheckoutSession($quote, $order);

                return $this->resultRedirectFactory->create()->setPath(self::SUCCESS_PATH);
            }

            // No Magento order was created but the shopper may already be authorized at
            // Sezzle. Release that authorization so it does not sit pending / expire.
            if ($quote !== null) {
                $this->orderRecovery->releaseStrandedAuthorization($quote);
            }
            $this->handleException($e);
        }

        return $this->resultRedirectFactory->create()->setPath($redirectPath);
    }

    /**
     * Place the order for the given quote, recovering from a reserved-order-id collision.
     *
     * @param CartInterface $quote
     * @return int|null
     * @throws \Throwable
     */
    private function placeOrder(CartInterface $quote): ?int
    {
        $cartManager = $this->customerSession->isLoggedIn() ? self::CART_MANAGER : self::GUEST_CART_MANAGER;
        $quoteId = (int)$quote->getId();
        $resolvedId = $cartManager === self::GUEST_CART_MANAGER
            ? $this->quoteIdToMaskedQuoteIdInterface->execute($quoteId)
            : $quoteId;

        try {
            return (int)$this->{$cartManager}->placeOrder($resolvedId);
        } catch (\Throwable $e) {
            // Only a collision on *this* checkout's reserved ID is recoverable here. Anything
            // else belongs to execute(), which logs it, looks for an already-placed order and
            // releases the authorization. The reserved ID is passed so that an unrelated unique
            // conflict - a duplicate coupon-usage row, a third-party unique index - does not
            // send an already-authorized payment back through submitQuote() and its observers.
            if (!$this->orderRecovery->isIncrementIdCollision($e, (string)$quote->getReservedOrderId())) {
                throw $e;
            }

            // The reserved increment ID collided with an existing sales_order row.
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Reserved order ID collision on placeOrder',
                'reserved_order_id' => $quote->getReservedOrderId(),
                'store_id' => $quote->getStoreId(),
                'error' => $e->getMessage()
            ]);

            // If the colliding order belongs to this quote, it was placed by a concurrent
            // request - treat it as success rather than surfacing a DB error.
            if ($order = $this->orderRecovery->getExistingOrder($quote)) {
                $this->restoreCheckoutSession($quote, $order);

                return (int)$order->getId();
            }

            // Otherwise the increment ID was consumed by an unrelated order. Reserve a fresh
            // one and retry once so this authorized payment still results in an order rather
            // than a stranded Sezzle authorization that the shopper has to re-attempt.
            //
            // Re-read first. The quote in hand has been through a submitQuote() that rolled
            // back: addresses converted, items mutated, is_active and orig_order_id possibly
            // set, all in memory while the database went back to where it started. Saving it
            // as-is would persist that half-converted cart and then resubmit it.
            $quote = $this->rereadQuote($quote, $quoteId);
            $quote->setReservedOrderId(null);
            $quote->reserveOrderId();
            $this->cartRepository->save($quote);
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Regenerated reserved order ID and retrying placeOrder',
                'reserved_order_id' => $quote->getReservedOrderId()
            ]);

            return (int)$this->{$cartManager}->placeOrder($resolvedId);
        }
    }

    /**
     * Re-read a quote from committed state, falling back to the one in hand.
     *
     * Best-effort: if the re-read fails there is still an authorized payment to place an order
     * for, and retrying with the in-memory quote is better than not retrying at all.
     *
     * @param CartInterface $quote
     * @param int $quoteId
     * @return CartInterface
     */
    private function rereadQuote(CartInterface $quote, int $quoteId): CartInterface
    {
        try {
            return $this->cartRepository->get($quoteId);
        } catch (\Throwable $rereadFailure) {
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Could not re-read the quote before retrying; retrying with the in-memory copy',
                'quote_id' => $quoteId,
                'error' => $rereadFailure->getMessage()
            ]);

            return $quote;
        }
    }

    /**
     * Populate the checkout session so the success page renders for an already-placed order.
     *
     * @param CartInterface $quote
     * @param Order $order
     * @return void
     */
    private function restoreCheckoutSession(CartInterface $quote, Order $order): void
    {
        $this->checkoutSession
            ->setLastQuoteId($quote->getId())
            ->setLastSuccessQuoteId($quote->getId())
            ->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());
    }

    /**
     * Record a placement failure with everything needed to diagnose it after the fact.
     *
     * The wrapper Magento throws carries only the message of whatever failed last, so the
     * originating error is invisible in both the error-report page and the storefront message.
     * Logging the whole previous chain alongside the quote, store and reserved increment ID is
     * what makes these reports diagnosable without asking the merchant for another log.
     *
     * Never throws: this runs on the failure path, ahead of the authorization release, and a
     * second exception here would cost the shopper that release.
     *
     * @param string $origin
     * @param \Throwable $e
     * @param CartInterface|null $quote
     * @return void
     */
    private function logPlacementFailure(string $origin, \Throwable $e, ?CartInterface $quote): void
    {
        // Unconditional, and first: logSezzleActions() below is gated on
        // payment/sezzlepay/log_tracker, so on a store with the tracker off this is the only
        // trace a failed checkout leaves anywhere - the catch in execute() does not rethrow,
        // so Magento writes no error report and an APM sees a clean redirect.
        $this->helper->logCriticalFailure('Sezzle order placement failed on return from Sezzle', $e);

        try {
            $this->helper->logSezzleActions([
                'log_origin' => $origin,
                'message' => 'Order placement failed on return from Sezzle',
                'quote_id' => $quote ? $quote->getId() : null,
                'store_id' => $quote ? $quote->getStoreId() : null,
                'reserved_order_id' => $quote ? $quote->getReservedOrderId() : null,
                'is_increment_id_collision' => $this->orderRecovery->isIncrementIdCollision($e),
                'exception_chain' => $this->orderRecovery->describeThrowable($e)
            ]);
        } catch (\Throwable $loggingFailure) {
            try {
                $this->helper->logSezzleActions(
                    'Could not log Sezzle placement failure: ' . $loggingFailure->getMessage()
                );
            } catch (\Throwable $ignored) {
                // The fallback goes through the same sink that just failed, and that sink only
                // swallows NoSuchEntityException and InputException. Anything else would escape
                // this method, escape execute()'s catch, and cost the shopper the authorization
                // release that runs after it - the exact outcome this handling exists to avoid.
            }
        }
    }

    /**
     * Look for an already-placed order for this quote, without ever throwing.
     *
     * Also runs on the failure path, so it must not displace the authorization release either.
     *
     * @param CartInterface|null $quote
     * @return Order|null
     */
    private function findRecoverableOrder(?CartInterface $quote): ?Order
    {
        if ($quote === null) {
            return null;
        }

        try {
            return $this->orderRecovery->getExistingOrder($quote);
        } catch (\Throwable $lookupFailure) {
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Could not check whether an order was already placed for this quote',
                'error' => $lookupFailure->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Handling Exception
     *
     * @param \Throwable $exc
     * @return void
     */
    private function handleException(\Throwable $exc): void
    {
        // Guarded for the same reason as logPlacementFailure(): this runs inside execute()'s
        // catch, so an exception from the log sink here escapes the controller and puts the
        // shopper back on the raw Magento error report page.
        try {
            $this->helper->logSezzleActions("Sezzle Transaction Exception: " . $exc->getMessage());
        } catch (\Throwable $loggingFailure) {
            $this->helper->logCriticalFailure('Could not write the Sezzle transaction log', $loggingFailure);
        }

        // Localized exceptions are written for shoppers; anything else is internal (a database
        // constraint name, a PHP error) and must not be echoed onto the storefront.
        //
        // A duplicate-key conflict is excluded by name rather than by type. AlreadyExistsException
        // extends LocalizedException, so the localized test alone would hand the shopper
        // "Unique constraint violation found, rule name is UNIQUE_SALES_ORDER_INCREMENT_ID..."
        // verbatim - the message for precisely the failure this controller exists to handle,
        // reachable whenever the retry above collides a second time.
        $isInternal = !($exc instanceof LocalizedException)
            || $this->orderRecovery->isIncrementIdCollision($exc);

        $this->messageManager->addErrorMessage(
            $isInternal
                ? __('We were unable to complete your order. Please try again or contact us for help.')
                : $exc->getMessage()
        );
    }
}
