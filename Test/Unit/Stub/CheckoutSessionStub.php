<?php

namespace Sezzle\Sezzlepay\Test\Unit\Stub;

use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Test double exposing Magento\Checkout\Model\Session's magic Last* setters as real methods so
 * they can be mocked under PHPUnit 12, where MockBuilder::addMethods() was removed.
 */
class CheckoutSessionStub extends CheckoutSession
{
    public function setLastQuoteId($quoteId)
    {
        return $this;
    }

    public function setLastSuccessQuoteId($quoteId)
    {
        return $this;
    }

    public function setLastOrderId($orderId)
    {
        return $this;
    }

    public function setLastRealOrderId($realOrderId)
    {
        return $this;
    }

    public function setLastOrderStatus($status)
    {
        return $this;
    }
}
