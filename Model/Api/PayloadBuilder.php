<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Sezzle\Sezzlepay\Model\Config\Container\SezzleApiConfigInterface;
use Sezzle\Sezzlepay\Model\SezzlePay;

/**
 * Class PayloadBuilder
 * @package Sezzle\Sezzlepay\Model\Api
 */
class PayloadBuilder
{
    const PRECISION = 2;

    /**
     * @var ConfigInterface
     */
    private $sezzleApiConfig;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * PayloadBuilder constructor.
     * @param StoreManagerInterface $storeManager
     * @param SezzleApiConfigInterface $sezzleApiConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        SezzleApiConfigInterface $sezzleApiConfig
    ) {
        $this->sezzleApiConfig = $sezzleApiConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Build Sezzle Checkout Payload
     * @param Quote $quote
     * @param string $reference
     * @return array
     * @throws NoSuchEntityException
     */
    public function buildSezzleCheckoutPayload($quote, $reference)
    {
        $orderPayload = [];
        $completeURL = [
            "href" => $this->sezzleApiConfig->getCompleteUrl($quote->getReservedOrderId(), $reference)
        ];
        $cancelURL = [
            "href" => $this->sezzleApiConfig->getCancelUrl()
        ];
        if ($this->sezzleApiConfig->isCheckoutAllowed()) {
            $orderPayload = $this->buildOrderPayload($quote, $reference);
        }
        $customerPayload = $this->buildCustomerPayload($quote);
        return array_merge(
            $completeURL,
            $cancelURL,
            $orderPayload,
            $customerPayload
        );
    }

    /**
     * Build Order Payload from Sezzle Checkout Session
     *
     * @param Quote $quote
     * @param string $reference
     * @return array
     * @throws NoSuchEntityException
     */
    private function buildOrderPayload($quote, $reference)
    {
        $intent = $this->sezzleApiConfig->getPaymentAction() == SezzlePay::ACTION_AUTHORIZE_CAPTURE
            ? "CAPTURE"
            : "AUTH";
        $checkoutPayload["intent"] = $intent;
        $checkoutPayload["reference_id"] = $reference;
        $checkoutPayload["description"] = $this->storeManager->getStore()->getName();
        $checkoutPayload["requires_shipping_info"] = false;
        $checkoutPayload["items"] = $this->buildItemPayload($quote);
        $checkoutPayload["discounts"] = $this->getPriceObject($quote->getShippingAddress()->getBaseDiscountAmount());
        $checkoutPayload["shipping_amount"] = $this->getPriceObject($quote->getShippingAddress()
            ->getBaseShippingAmount());
        $checkoutPayload["tax_amount"] = $this->getPriceObject($quote->getShippingAddress()->getBaseTaxAmount());
        $checkoutPayload["order_amount"] = $this->getPriceObject($quote->getBaseGrandTotal());
        return $checkoutPayload;
    }

    /**
     * Get Price Object
     *
     * @param float $amount
     * @return array
     */
    private function getPriceObject($amount)
    {
        try {
            return [
                "amount_in_cents" => $this->convertAmtToCents($amount),
                "currency" => $this->storeManager->getStore()->getCurrentCurrencyCode()
            ];
        } catch (NoSuchEntityException $e) {
        }
    }

    /**
     * Convert amount into cents
     *
     * @param float $amount
     * @return int
     */
    private function convertAmtToCents($amount)
    {
        return (int)(round($amount * 100, self::PRECISION));
    }

    /**
     * Build Customer Payload
     * @param Quote $quote
     * @return array
     */
    private function buildCustomerPayload($quote)
    {
        $billingAddress = $quote->getBillingAddress();
        $customerPayload["customer"] = [
            "tokenize" => $this->sezzleApiConfig->isTokenizationAllowed(),
            "email" => $quote->getCustomerEmail(),
            "first_name" => $quote->getCustomerFirstname()
                ? $quote->getCustomerFirstname()
                : $billingAddress->getFirstname(),
            "last_name" => $quote->getCustomerLastname()
                ? $quote->getCustomerLastname()
                : $billingAddress->getLastname(),
            "phone" => $billingAddress->getTelephone(),
            "dob" => $quote->getCustomer()->getDob(),
            "billing_address" => $this->buildBillingPayload($quote),
            "shipping_address" => $this->buildShippingPayload($quote),
        ];
        return $customerPayload;
    }

    /**
     * Build Billing Address Payload
     * @param Quote $quote
     * @return array
     */
    private function buildBillingPayload($quote)
    {
        /** @var Quote\Address $billingAddress */
        $billingAddress = $quote->getBillingAddress();
        $billingPayload["billing_address"] = [
            "name" => $billingAddress->getName(),
            "street" => $billingAddress->getStreetLine(1),
            "street2" => $billingAddress->getStreetLine(2),
            "city" => $billingAddress->getCity(),
            "state" => $billingAddress->getRegionCode(),
            "postal_code" => $billingAddress->getPostcode(),
            "country_code" => $billingAddress->getCountryId(),
            "phone" => $billingAddress->getTelephone()
        ];
        return $billingPayload;
    }

    /**
     * Build Shipping Address Payload
     * @param Quote $quote
     * @return array
     */
    private function buildShippingPayload($quote)
    {
        /** @var Quote\Address $shippingAddress */
        $shippingAddress = $quote->getShippingAddress();
        $shippingPayload["shipping_address"] = [
            "name" => $shippingAddress->getName(),
            "street" => $shippingAddress->getStreetLine(1),
            "street2" => $shippingAddress->getStreetLine(2),
            "city" => $shippingAddress->getCity(),
            "state" => $shippingAddress->getRegionCode(),
            "postal_code" => $shippingAddress->getPostcode(),
            "country_code" => $shippingAddress->getCountryId(),
            "phone" => $shippingAddress->getTelephone()
        ];
        return $shippingPayload;
    }

    /**
     * Build Cart Item Payload
     * @param Quote $quote
     * @return array
     * @throws NoSuchEntityException
     */
    private function buildItemPayload($quote)
    {
        $currencyCode = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $itemPayload["items"] = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $productName = $item->getName();
            $productSku = $item->getSku();
            $productQuantity = $item->getQtyOrdered();
            $itemData = [
                "name" => $productName,
                "sku" => $productSku,
                "quantity" => $productQuantity,
                "price" => [
                    "amount_in_cents" => (int)(round($item->getPriceInclTax() * 100, self::PRECISION)),
                    "currency" => $currencyCode
                ]
            ];
            array_push($itemPayload["items"], $itemData);
        }
        return $itemPayload;
    }
}
