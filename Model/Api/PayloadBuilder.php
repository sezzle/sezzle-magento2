<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Sezzle\Payment\Model\System\Config\Container\SezzleApiConfigInterface;
use Sezzle\Payment\Model\Sezzle;

/**
 * Class PayloadBuilder
 * @package Sezzle\Payment\Model\Api
 */
class PayloadBuilder
{
    const PRECISION = 2;

    /**
     * @var SezzleApiConfigInterface
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
        $this->storeManager = $storeManager;
        $this->sezzleApiConfig = $sezzleApiConfig;
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
        $completeURL['complete_url'] = [
            "href" => $this->sezzleApiConfig->getCompleteUrl()
        ];
        $cancelURL['cancel_url'] = [
            "href" => $this->sezzleApiConfig->getCancelUrl()
        ];
        $orderPayload['order'] = $this->buildOrderPayload($quote, $reference);
        $customerPayload['customer'] = $this->buildCustomerPayload($quote);
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
        $intent = $this->sezzleApiConfig->getPaymentAction() == Sezzle::ACTION_AUTHORIZE_CAPTURE
            ? "CAPTURE"
            : "AUTH";
        return [
            "intent" => $intent,
            "reference_id" => $reference,
            "description" => $this->storeManager->getStore()->getName(),
            "requires_shipping_info" => false,
            "items" => $this->buildItemPayload($quote),
            "discounts" => [$this->getPriceObject($quote->getShippingAddress()->getBaseDiscountAmount())],
            "shipping_amount" => $this->getPriceObject($quote->getShippingAddress()
                ->getBaseShippingAmount()),
            "tax_amount" => $this->getPriceObject($quote->getShippingAddress()->getBaseTaxAmount()),
            "order_amount" => $this->getPriceObject($quote->getBaseGrandTotal()),
        ];
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
        return [
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
        return [
            "name" => $billingAddress->getName(),
            "street" => $billingAddress->getStreetLine(1),
            "street2" => $billingAddress->getStreetLine(2),
            "city" => $billingAddress->getCity(),
            "state" => $billingAddress->getRegionCode(),
            "postal_code" => $billingAddress->getPostcode(),
            "country_code" => $billingAddress->getCountryId(),
            "phone" => $billingAddress->getTelephone()
        ];
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
        return [
            "name" => $shippingAddress->getName(),
            "street" => $shippingAddress->getStreetLine(1),
            "street2" => $shippingAddress->getStreetLine(2),
            "city" => $shippingAddress->getCity(),
            "state" => $shippingAddress->getRegionCode(),
            "postal_code" => $shippingAddress->getPostcode(),
            "country_code" => $shippingAddress->getCountryId(),
            "phone" => $shippingAddress->getTelephone()
        ];
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
        $itemPayload = [];
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
            array_push($itemPayload, $itemData);
        }
        return $itemPayload;
    }
}
