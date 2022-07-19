<?php

namespace Sezzle\Sezzlepay\Api;

interface CheckoutInterface
{
    /**
     * Gets the standard checkout URL
     *
     * @return string|null
     */
    public function getCheckoutURL(): ?string;
}
