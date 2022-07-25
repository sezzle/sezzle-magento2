<?php

namespace Sezzle\Sezzlepay\Model\GraphQl\Resolver;

use InvalidArgumentException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Sezzle\Sezzlepay\Api\CheckoutInterface;
use Sezzle\Sezzlepay\Gateway\Config\Config;

/**
 * CreateSezzleCheckout
 */
class CreateSezzleCheckout implements ResolverInterface
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CheckoutInterface
     */
    private $checkout;

    /**
     * CreateSezzleCheckout constructor
     * @param Config $config
     * @param GetCartForUser $getCartForUser
     * @param CheckoutInterface $checkout
     */
    public function __construct(
        Config            $config,
        GetCartForUser    $getCartForUser,
        CheckoutInterface $checkout
    )
    {
        $this->config = $config;
        $this->getCartForUser = $getCartForUser;
        $this->checkout = $checkout;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

        switch (true) {
            case !$this->config->isEnabled($storeId):
                throw new GraphQlInputException(__('Sezzle payment method is not enabled.'));
            case !$args || !$args['input']:
                throw new InvalidArgumentException('Required param "cart_id" is missing.');
        }

        $checkoutURL = $this->checkout->getCheckoutURL();
        if (!$checkoutURL) {
            throw new GraphQlInputException(__('Unable to create Sezzle checkout.'));
        }

        return [
            'checkout_url' => $checkoutURL
        ];
    }
}
