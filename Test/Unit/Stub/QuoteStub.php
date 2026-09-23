<?php

namespace Sezzle\Sezzlepay\Test\Unit\Stub;

use Magento\Quote\Model\Quote;

/**
 * Test double exposing Magento\Quote\Model\Quote's magic getCustomerEmail/getBase* getters as
 * real methods so they can be mocked under PHPUnit 12, where MockBuilder::addMethods() was removed.
 */
class QuoteStub extends Quote
{
    public function getCustomerEmail()
    {
        return null;
    }

    public function getBaseGrandTotal()
    {
        return null;
    }

    public function getBaseCurrencyCode()
    {
        return null;
    }
}
