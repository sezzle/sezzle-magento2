<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Checkout;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * Class CheckoutValidator
 * @package Sezzle\Sezzlepay\Model\Order
 */
class CheckoutValidator
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
     * Validate Checkout
     *
     * @param Quote|CartInterface $quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function validate(Quote $quote)
    {
        $this->validateAddress($quote);
        $this->validateShippingMethod($quote);
    }

    /**
     * Validate Addresses
     *
     * @param Quote $quote
     * @throws LocalizedException
     */
    protected function validateAddress(Quote $quote)
    {
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();

        $shippingAddressMissingFields = "";
        foreach ($this->requiredFields as $field) {
            if (!$shippingAddress->getData($field)) {
                $shippingAddressMissingFields .= $field . ",";
            }
        }
        if ($shippingAddressMissingFields) {
            $this->sezzleHelper->logSezzleActions(sprintf('Invalid Shipping Address : %s', $shippingAddressMissingFields));
            throw new LocalizedException(__(sprintf("Please check the shipping address on this input fields : %s", rtrim($shippingAddressMissingFields, ","))));
        }

        $billingAddressMissingFields = "";
        foreach ($this->requiredFields as $field) {
            if (!$billingAddress->getData($field)) {
                $billingAddressMissingFields .= $field . ",";
            }
        }
        if ($billingAddressMissingFields) {
            $this->sezzleHelper->logSezzleActions(sprintf('Invalid Billing Address : %s', $billingAddressMissingFields));
            throw new LocalizedException(__(sprintf("Please check the billing address on this input fields : %s", rtrim($billingAddressMissingFields, ","))));
        }
        $this->sezzleHelper->logSezzleActions("Address Validated!");
    }

    /**
     * Validate Shipping Method
     *
     * @param Quote $quote
     * @throws LocalizedException
     */
    protected function validateShippingMethod(Quote $quote)
    {
        if (!$quote->getShippingAddress()->getShippingMethod()) {
            $this->sezzleHelper->logSezzleActions('Please select a shipping method');
            throw new LocalizedException(__('Please select a shipping method'));
        }
    }
}
