<?php

declare(strict_types=1);

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
}
