<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * Class AddressValidator
 * @package Sezzle\Sezzlepay\Model\Order
 */
class AddressValidator
{
    /**
     * @var string[]
     */
    private $requiredFields = [
        "firstname",
        "lastname",
        "street",
        "city",
        "region_id",
        "postcode",
        "country_id",
        "telephone"
    ];
    /**
     * @var Data
     */
    private $sezzleHelper;

    /**
     * AddressValidator constructor.
     * @param Data $sezzleHelper
     */
    public function __construct(Data $sezzleHelper)
    {
        $this->sezzleHelper = $sezzleHelper;
    }

    /**
     * @param Quote $quote
     * @throws LocalizedException
     */
    public function validateAddress(Quote $quote)
    {
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();

        foreach ($this->requiredFields as $field) {
            if (!$shippingAddress->getData($field)) {
                $this->sezzleHelper->logSezzleActions(sprintf('Invalid Shipping Address : %s', $field));
                throw new LocalizedException(__("Invalid shipping information. Please check the input data."));
            }
        }
        $isBillingAddressValid = true;
        foreach ($this->requiredFields as $field) {
            if (!$billingAddress->getData($field)) {
                $this->sezzleHelper->logSezzleActions(sprintf('Invalid Billing Address : %s', $field));
                $isBillingAddressValid = false;
            }
        }
        if (!$isBillingAddressValid) {
            $this->sezzleHelper->logSezzleActions('Billing Address was invalid. Cloning the shipping address into billing address.');
            $quote->setBillingAddress($shippingAddress);
        }
        $this->sezzleHelper->logSezzleActions("Address Validated!");
    }
}
