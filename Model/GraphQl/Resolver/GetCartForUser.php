<?php

namespace Sezzle\Sezzlepay\Model\GraphQl\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser as BaseGetCartForUser;

/**
 * GetCartForUser
 */
class GetCartForUser
{

    /**
     * @var BaseGetCartForUser
     */
    private $getCartForUser;

    /**
     * GetCartForUser constructor
     * @param BaseGetCartForUser $getCartForUser
     */
    public function __construct(
        BaseGetCartForUser $getCartForUser
    )
    {
        $this->getCartForUser = $getCartForUser;
    }

    /**
     * Wrapper for base getCartForUser->execute
     * @throws NoSuchEntityException
     * @throws GraphQlAuthorizationException
     * @throws GraphQlNoSuchEntityException
     * @throws GraphQlInputException
     */
    public function getCart(string $maskedCartId, ContextInterface $context): Quote
    {
        $storeId = $context->getExtensionAttributes()->getStore()->getId();
        return $this->getCartForUser->execute($maskedCartId, $context->getUserId(), $storeId);
    }

}
