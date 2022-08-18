<?php

namespace Sezzle\Sezzlepay\Model\GraphQl\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Sezzle\Sezzlepay\Api\CartManagementInterface;
use Magento\QuoteGraphQl\Model\Cart\CheckCartCheckoutAllowance;

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
     * PlaceSezzleOrder constructor
     * @param Validator $validator
     * @param GetCartForUser $getCartForUser
     * @param CheckCartCheckoutAllowance $checkCartCheckoutAllowance
     * @param CartManagementInterface $cartManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentMethodManagementInterface $paymentMethodManagement
     */
    public function __construct(
        Validator                        $validator,
        GetCartForUser                   $getCartForUser,
        CheckCartCheckoutAllowance       $checkCartCheckoutAllowance,
        CartManagementInterface          $cartManagement,
        OrderRepositoryInterface         $orderRepository,
        PaymentMethodManagementInterface $paymentMethodManagement
    )
    {
        $this->validator = $validator;
        $this->getCartForUser = $getCartForUser;
        $this->checkCartCheckoutAllowance = $checkCartCheckoutAllowance;
        $this->cartManagement = $cartManagement;
        $this->orderRepository = $orderRepository;
        $this->paymentMethodManagement = $paymentMethodManagement;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
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
            $orderId = $this->cartManagement->placeOrder($cartId, $this->paymentMethodManagement->get($cartId));
            $order = $this->orderRepository->get($orderId);

            return [
                'order' => [
                    'order_number' => $order->getIncrementId(),
                    'order_id' => $orderId,
                ],
            ];
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(
                __('Unable to place Sezzle order: %message', ['message' => $e->getMessage()]), $e);
        }
    }
}
