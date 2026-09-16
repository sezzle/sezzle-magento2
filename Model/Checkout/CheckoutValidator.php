<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Checkout;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Helper\Util;

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
     * @var Config
     */
    private $config;

    /**
     * AddressValidator constructor.
     * @param Data $sezzleHelper
     * @param Config $config
     */
    public function __construct(Data $sezzleHelper, Config $config)
    {
        $this->sezzleHelper = $sezzleHelper;
        $this->config = $config;
    }

    /**
     * Validate Checkout
     *
     * Shipping is validated first so that a quote which is missing both addresses
     * reports the address the shopper actually has to fix, rather than a billing
     * address that is only ever a copy of it.
     *
     * @param Quote|CartInterface $quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function validate(Quote $quote)
    {
        if (!$quote->isVirtual()) {
            $this->validateAddress($quote->getShippingAddress());
            $this->validateShippingMethod($quote);
            $this->applyShippingAddressAsBilling($quote);
        }

        // A virtual quote has no shipping address to fall back on, so its billing address
        // is the only one Magento can hand to BillingAddressValidationRule when the order
        // is placed on the return leg from Sezzle. Keep it required there.
        $this->validateAddress($quote->getBillingAddress());
    }

    /**
     * Fill an empty billing address from the shipping address
     *
     * Only when the merchant has turned "Require Billing Address" off. The storefront
     * normally sends a billing address even then - with the setting off Magento renders
     * no billing form and checkout-data-resolver reuses the shipping address, which
     * createCheckout() saves onto the quote before this runs. Callers that do not go
     * through that JS, such as headless storefronts and third party checkouts, send
     * nothing. Leaving the quote without a billing address would let the shopper
     * authorize at Sezzle and only then fail, because Magento validates billing during
     * order submission.
     *
     * @param Quote $quote
     * @return void
     */
    private function applyShippingAddressAsBilling(Quote $quote): void
    {
        $billingAddress = $quote->getBillingAddress();
        if (!Util::isAddressEmpty($billingAddress) || $this->isBillingAddressRequired($quote)) {
            return;
        }

        // importCustomerAddressData() is the counterpart of exportCustomerAddress() and
        // flattens the exported region back into region/region_id/region_code, which is
        // the shape a quote address stores.
        $billingAddress->importCustomerAddressData(
            $quote->getShippingAddress()->exportCustomerAddress()
        );

        $this->sezzleHelper->logSezzleActions([
            'quote_id' => $quote->getId(),
            'log_origin' => __METHOD__,
            'message' => 'Billing address is empty and not required. Copied from the shipping address.'
        ]);
    }

    /**
     * Whether a billing address must be collected for this quote
     *
     * A config lookup failure keeps it required. Rejecting the session request is
     * recoverable; failing after the shopper has authorized at Sezzle is not.
     *
     * @param Quote $quote
     * @return bool
     */
    private function isBillingAddressRequired(Quote $quote): bool
    {
        try {
            return $this->config->isBillingAddressRequired((int)$quote->getStoreId());
        } catch (Exception $e) {
            $this->sezzleHelper->logSezzleActions([
                'quote_id' => $quote->getId(),
                'log_origin' => __METHOD__,
                'message' => 'Could not resolve the billing address requirement. Leaving it required.',
                'error' => $e->getMessage()
            ]);

            return true;
        }
    }

    /**
     * Validate Addresses
     *
     * @param Quote\Address $address
     * @throws LocalizedException
     */
    protected function validateAddress(Quote\Address $address)
    {
        $missingFields = "";
        foreach ($this->requiredFields as $field) {
            if (!$address->getData($field)) {
                $missingFields .= $field . ",";
            }
        }
        if ($missingFields) {
            $this->sezzleHelper->logSezzleActions(
                sprintf('Invalid %s address : %s', $address->getAddressType(), $missingFields));
            throw new LocalizedException(
                __(sprintf("Please check the %s address on this input fields : %s",
                    $address->getAddressType(), rtrim($missingFields, ","))));
        }
        $this->sezzleHelper->logSezzleActions("Address Validated");
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
