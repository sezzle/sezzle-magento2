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
            // Only a collision is recoverable here. Anything else belongs to execute(), which
            // logs it, looks for an already-placed order and releases the authorization.
            if (!$this->orderRecovery->isIncrementIdCollision($e)) {
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
            $this->helper->logSezzleActions(
                'Could not log Sezzle placement failure: ' . $loggingFailure->getMessage()
            );
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
     */
    private function handleException(\Throwable $exc)
    {
        $this->helper->logSezzleActions("Sezzle Transaction Exception: " . $exc->getMessage());

        // Localized exceptions are written for shoppers; anything else is internal (a database
        // constraint name, a PHP error) and must not be echoed onto the storefront.
        $this->messageManager->addErrorMessage(
            $exc instanceof LocalizedException
                ? $exc->getMessage()
                : __('We were unable to complete your order. Please try again or contact us for help.')
        );
    }
}
