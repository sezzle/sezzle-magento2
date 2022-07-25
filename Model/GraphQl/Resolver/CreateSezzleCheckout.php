<?php

namespace Sezzle\Sezzlepay\Model\GraphQl\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Sezzle\Sezzlepay\Api\CheckoutInterface;

/**
 * CreateSezzleCheckout
 */
class CreateSezzleCheckout implements ResolverInterface
{

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
     * CreateSezzleCheckout constructor
     * @param CheckoutInterface $checkout
     * @param Validator $validator
     * @param GetCartForUser $getCartForUser
     */
    public function __construct(
        CheckoutInterface $checkout,
        Validator         $validator,
        GetCartForUser    $getCartForUser
    )
    {
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

        $checkoutURL = $this->checkout->getCheckoutURL($cart->getId());
        if (!$checkoutURL) {
            throw new GraphQlInputException(__('Unable to create Sezzle checkout.'));
        }

        return [
            'checkout_url' => $checkoutURL
        ];
    }
}
