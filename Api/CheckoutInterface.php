<?php

namespace Sezzle\Sezzlepay\Api;

interface CheckoutInterface
{
    /**
     * Gets the standard checkout URL
     *
     * @param int $cartId
     * @return string|null
     */
    public function getCheckoutURL(int $cartId): ?string;


    /**
     * Gets the express checkout URL
     *
     * @param int $cartId
     * @return string|null
     */
    public function getExpressCheckoutURL(int $cartId): ?string;
}
