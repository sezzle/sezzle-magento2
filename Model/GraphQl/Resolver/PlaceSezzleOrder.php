<?php

namespace Sezzle\Sezzlepay\Model\GraphQl\Resolver;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
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
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        } catch (LocalizedException $e) {
            // Last-resort recovery: the exception may have come from an observer or
            // post-processing step that ran after the order was already persisted. If the
            // order exists, return it rather than releasing a live authorization and
            // surfacing an error for an order the shopper actually placed.
            if ($order = $this->orderRecovery->getExistingOrder($cart)) {
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
            throw new GraphQlInputException(
                __('Unable to place Sezzle order: %message', ['message' => $e->getMessage()]), $e);
        }
    }

    /**
     * Place the order, recovering from a reserved-order-id collision.
     *
     * @param CartInterface $cart
     * @param int $cartId
     * @return OrderInterface
     * @throws LocalizedException
     */
    private function placeWithCollisionRecovery(CartInterface $cart, $cartId): OrderInterface
    {
        try {
            $orderId = $this->cartManagement->placeOrder($cartId, $this->paymentMethodManagement->get($cartId));
        } catch (AlreadyExistsException $e) {
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Reserved order ID collision on placeOrder',
                'reserved_order_id' => $cart->getReservedOrderId(),
                'error' => $e->getMessage()
            ]);

            // The colliding order belongs to this cart - it was placed by a concurrent
            // request. Return it rather than surfacing a DB error.
            if ($existing = $this->orderRecovery->getExistingOrder($cart)) {
                return $existing;
            }

            // The increment ID was consumed by an unrelated order. Reserve a fresh one and
            // retry once so the authorized payment still results in an order.
            $cart->setReservedOrderId(null);
            $cart->reserveOrderId();
            $this->cartRepository->save($cart);
            $orderId = $this->cartManagement->placeOrder($cartId, $this->paymentMethodManagement->get($cartId));
        }

        return $this->orderRepository->get($orderId);
    }
}
