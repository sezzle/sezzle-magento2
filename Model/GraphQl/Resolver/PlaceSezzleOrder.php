<?php

namespace Sezzle\Sezzlepay\Model\GraphQl\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Phrase;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Sezzle\Sezzlepay\Api\CartManagementInterface;
use Magento\QuoteGraphQl\Model\Cart\CheckCartCheckoutAllowance;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\OrderRecoveryService;

/**
 * PlaceSezzleOrder
 */
class PlaceSezzleOrder implements ResolverInterface
{

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * @var CheckCartCheckoutAllowance
     */
    private $checkCartCheckoutAllowance;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var PaymentMethodManagementInterface
     */
    private $paymentMethodManagement;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var OrderRecoveryService
     */
    private $orderRecovery;

    /**
     * @var Data
     */
    private $helper;

    /**
     * PlaceSezzleOrder constructor
     * @param Validator $validator
     * @param GetCartForUser $getCartForUser
     * @param CheckCartCheckoutAllowance $checkCartCheckoutAllowance
     * @param CartManagementInterface $cartManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     * @param CartRepositoryInterface $cartRepository
     * @param OrderRecoveryService $orderRecovery
     * @param Data $helper
     */
    public function __construct(
        Validator                        $validator,
        GetCartForUser                   $getCartForUser,
        CheckCartCheckoutAllowance       $checkCartCheckoutAllowance,
        CartManagementInterface          $cartManagement,
        OrderRepositoryInterface         $orderRepository,
        PaymentMethodManagementInterface $paymentMethodManagement,
        CartRepositoryInterface          $cartRepository,
        OrderRecoveryService             $orderRecovery,
        Data                             $helper
    )
    {
        $this->validator = $validator;
        $this->getCartForUser = $getCartForUser;
        $this->checkCartCheckoutAllowance = $checkCartCheckoutAllowance;
        $this->cartManagement = $cartManagement;
        $this->orderRepository = $orderRepository;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->cartRepository = $cartRepository;
        $this->orderRecovery = $orderRecovery;
        $this->helper = $helper;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $this->validator->validateInput($context, $args);

        $cart = $this->getCartForUser->getCart($args['input']['cart_id'], $context);
        $this->checkCartCheckoutAllowance->execute($cart);

        if ((int)$context->getUserId() === 0) {
            if (!$cart->getCustomerEmail()) {
                throw new GraphQlInputException(__("Guest email for cart is missing."));
            }
            $cart->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);
        }

        try {
            $cartId = $cart->getId();

            // Idempotency: this mutation can be retried for the same cart (network retry,
            // double submit). Re-submitting reuses the reserved increment ID and fails with
            // "Unique constraint violation found", stranding the Sezzle authorization. If the
            // order already exists for this cart, return it instead of resubmitting.
            $order = $this->orderRecovery->getExistingOrder($cart);
            if ($order === null) {
                $order = $this->placeWithCollisionRecovery($cart, $cartId);
            }

            return [
                'order' => [
                    'order_number' => $order->getIncrementId(),
                    'order_id' => $order->getId(),
                ],
            ];
        } catch (\Throwable $e) {
            // Catch broadly, by design - see the matching comment in
            // Controller\Payment\Complete::execute(). Magento wraps a failure that occurs while
            // rolling back a failed submitQuote() in a plain \Exception, so matching on
            // LocalizedException alone let the most damaging case through unhandled.
            $this->logPlacementFailure(__METHOD__, $e, $cart);

            // Last-resort recovery: the exception may have come from an observer or
            // post-processing step that ran after the order was already persisted. If the
            // order exists, return it rather than releasing a live authorization and
            // surfacing an error for an order the shopper actually placed.
            if ($order = $this->findRecoverableOrder($cart)) {
                $this->helper->logSezzleActions([
                    'log_origin' => __METHOD__,
                    'message' => 'Recovered already-placed order after exception',
                    'order_id' => $order->getId(),
                    'error' => $e->getMessage()
                ]);
                return [
                    'order' => [
                        'order_number' => $order->getIncrementId(),
                        'order_id' => $order->getId(),
                    ],
                ];
            }

            // No Magento order was created but the shopper may already be authorized at
            // Sezzle. Release that authorization so it does not sit pending / expire.
            $this->orderRecovery->releaseStrandedAuthorization($cart);

            // GraphQl exceptions only accept an \Exception as their cause, so an \Error (a PHP
            // type error in an observer, say) is reported without one rather than fataling here.
            $cause = $e instanceof \Exception ? $e : null;

            // Localized exceptions are written for shoppers; anything else is internal (a
            // database constraint name, a PHP error) and must not reach the client.
            //
            // A duplicate-key conflict is excluded by name rather than by type, because
            // AlreadyExistsException extends LocalizedException: without this, the message for
            // the very failure this resolver exists to handle - "Unique constraint violation
            // found, rule name is UNIQUE_SALES_ORDER_INCREMENT_ID..." - would be returned
            // verbatim whenever the retry in placeWithCollisionRecovery() collided again.
            $isInternal = !($e instanceof LocalizedException)
                || $this->orderRecovery->isIncrementIdCollision($e);

            if ($e instanceof NoSuchEntityException) {
                // Wrapped in a Phrase rather than passed through __(): the message is not a
                // translation key, and handing untrusted text to the translator would have any
                // %1 in it interpreted as a placeholder.
                throw new GraphQlNoSuchEntityException(
                    $isInternal
                        ? __('Unable to place Sezzle order: %message', ['message' => __('an internal error occurred')])
                        : new Phrase($e->getMessage()),
                    $cause
                );
            }

            $reason = $isInternal
                ? (string)__('an internal error occurred')
                : $e->getMessage();

            throw new GraphQlInputException(
                __('Unable to place Sezzle order: %message', ['message' => $reason]), $cause);
        }
    }

    /**
     * Record a placement failure with everything needed to diagnose it after the fact.
     *
     * Never throws: this runs on the failure path, ahead of the authorization release, and a
     * second exception here would cost the shopper that release.
     *
     * @param string $origin
     * @param \Throwable $e
     * @param CartInterface $cart
     * @return void
     */
    private function logPlacementFailure(string $origin, \Throwable $e, CartInterface $cart): void
    {
        // Unconditional, and first: logSezzleActions() below is gated on
        // payment/sezzlepay/log_tracker, so on a store with the tracker off this is the only
        // trace a failed checkout leaves anywhere - the catch in resolve() turns the failure
        // into a GraphQl error, so Magento writes no error report of its own.
        $this->helper->logCriticalFailure('Sezzle order placement failed for GraphQl cart', $e);

        try {
            $this->helper->logSezzleActions([
                'log_origin' => $origin,
                'message' => 'Order placement failed for Sezzle cart',
                'quote_id' => $cart->getId(),
                'store_id' => $cart->getStoreId(),
                'reserved_order_id' => $cart->getReservedOrderId(),
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
                // this method, escape resolve()'s catch, and cost the shopper the authorization
                // release that runs after it - the exact outcome this handling exists to avoid.
            }
        }
    }

    /**
     * Look for an already-placed order for this cart, without ever throwing.
     *
     * Also runs on the failure path, so it must not displace the authorization release either.
     *
     * @param CartInterface $cart
     * @return OrderInterface|null
     */
    private function findRecoverableOrder(CartInterface $cart): ?OrderInterface
    {
        try {
            return $this->orderRecovery->getExistingOrder($cart);
        } catch (\Throwable $lookupFailure) {
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Could not check whether an order was already placed for this cart',
                'error' => $lookupFailure->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Re-read a cart from committed state, falling back to the one in hand.
     *
     * Best-effort: if the re-read fails there is still an authorized payment to place an order
     * for, and retrying with the in-memory cart is better than not retrying at all.
     *
     * @param CartInterface $cart
     * @param int $cartId
     * @return CartInterface
     */
    private function rereadCart(CartInterface $cart, int $cartId): CartInterface
    {
        try {
            return $this->orderRecovery->loadCommittedQuote($cartId);
        } catch (\Throwable $rereadFailure) {
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Could not re-read the cart before retrying; retrying with the in-memory copy',
                'quote_id' => $cartId,
                'error' => $rereadFailure->getMessage()
            ]);

            return $cart;
        }
    }

    /**
     * Place the order, recovering from a reserved-order-id collision.
     *
     * @param CartInterface $cart
     * @param int $cartId
     * @return OrderInterface
     * @throws \Throwable
     */
    private function placeWithCollisionRecovery(CartInterface $cart, $cartId): OrderInterface
    {
        try {
            $orderId = $this->cartManagement->placeOrder($cartId, $this->paymentMethodManagement->get($cartId));
        } catch (\Throwable $e) {
            // Only a collision on *this* checkout's reserved ID is recoverable here. Anything
            // else belongs to resolve(), which logs it, looks for an already-placed order and
            // releases the authorization. The reserved ID is passed so that an unrelated unique
            // conflict - a duplicate coupon-usage row, a third-party unique index - does not
            // send an already-authorized payment back through submitQuote() and its observers.
            if (!$this->orderRecovery->isIncrementIdCollision($e, (string)$cart->getReservedOrderId())) {
                throw $e;
            }

            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Reserved order ID collision on placeOrder',
                'reserved_order_id' => $cart->getReservedOrderId(),
                'store_id' => $cart->getStoreId(),
                'error' => $e->getMessage()
            ]);

            // The colliding order belongs to this cart - it was placed by a concurrent
            // request. Return it rather than surfacing a DB error.
            if ($existing = $this->orderRecovery->getExistingOrder($cart)) {
                return $existing;
            }

            // The increment ID was consumed by an unrelated order. Reserve a fresh one and
            // retry once so the authorized payment still results in an order.
            //
            // Re-read first. The cart in hand has been through a submitQuote() that rolled
            // back: addresses converted, items mutated, is_active and orig_order_id possibly
            // set, all in memory while the database went back to where it started. Saving it
            // as-is would persist that half-converted cart and then resubmit it. The save still
            // goes through the cart repository on purpose: it evicts the repository's cached copy,
            // which is the stale one, so the retry's getActive() loads committed state.
            $cart = $this->rereadCart($cart, (int)$cart->getId());
            $cart->setReservedOrderId(null);
            $cart->reserveOrderId();
            $this->cartRepository->save($cart);
            $orderId = $this->cartManagement->placeOrder($cartId, $this->paymentMethodManagement->get($cartId));
        }

        return $this->orderRepository->get($orderId);
    }
}
