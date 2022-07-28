<?php

namespace Sezzle\Sezzlepay\Model\GraphQl\Resolver;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Sezzle\Sezzlepay\Api\CheckoutInterface;
use Sezzle\Sezzlepay\Api\CustomerInterface;

/**
 * CreateSezzleCustomerOrder
 */
class CreateSezzleCustomerOrder implements ResolverInterface
{

    /**
     * @var CustomerInterface
     */
    private $customer;

    /**
     * @var CheckoutInterface
     */
    private $checkout;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var GetCartForUser
     */
    private $getCartForUser;

    /**
     * CreateSezzleCustomerOrder constructor
     * @param CustomerInterface $customer
     * @param CheckoutInterface $checkout
     * @param Validator $validator
     * @param GetCartForUser $getCartForUser
     */
    public function __construct(
        CustomerInterface $customer,
        CheckoutInterface $checkout,
        Validator         $validator,
        GetCartForUser    $getCartForUser
    )
    {
        $this->customer = $customer;
        $this->checkout = $checkout;
        $this->validator = $validator;
        $this->getCartForUser = $getCartForUser;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->validator->validateInput($context);

        $cart = $this->getCartForUser->getCart($args['input']['cart_id'], $context);

        try {
            $this->customer->createOrder($cart->getId());
        } catch (Exception $e) {
            // trying to create standard checkout as the tokenized order creation failed
            $checkoutURL = $this->checkout->getCheckoutURL($cart->getId());

            if (!$checkoutURL) {
                throw new GraphQlInputException(__('Something went wrong while placing order at Sezzle.'));
            }

            return [
                'success' => true,
                'checkout_url' => $checkoutURL,
            ];
        }

        return [
            'success' => true
        ];
    }
}
