<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Api;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\Resolver;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Helper\Util;
use Sezzle\Sezzlepay\Gateway\Config\Config;

/**
 * Class PayloadBuilder
 * @package Sezzle\Sezzlepay\Model\Api
 */
class PayloadBuilder
{

    /**
     * @var Config
     */
    private $config;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Data
     */
    private $sezzleHelper;
    /**
     * @var Resolver
     */
    private $localeResolver;

    /**
     * PayloadBuilder constructor.
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param Data $sezzleHelper
     * @param Resolver $localeResolver
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Config                $config,
        Data                  $sezzleHelper,
        Resolver              $localeResolver
    )
    {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->sezzleHelper = $sezzleHelper;
        $this->localeResolver = $localeResolver;
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
        $orderPayload['order'] = $this->buildOrderPayload($quote, $reference);
        $customerPayload['customer'] = $this->buildCustomerPayload($quote);
        $completeURL['complete_url'] = [
            "href" => $this->config->getCompleteURL()
        ];
        $cancelURL['cancel_url'] = [
            "href" => $this->config->getCancelURL()
        ];
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
     * @throws InputException
     */
    private function buildOrderPayload($quote, $reference)
    {
        $this->sezzleHelper->logSezzleActions("Order Total : " . $quote->getBaseGrandTotal());
        $orderPayload = [
            "intent" => "AUTH",
            "reference_id" => $reference,
            "description" => $this->storeManager->getStore()->getName(),
            "requires_shipping_info" => false,
            "items" => $this->buildItemPayload($quote),
            "discounts" => [
                $this->getPriceObject(
                    $quote->getShippingAddress()->getBaseDiscountAmount(),
                    $quote->getBaseCurrencyCode()
                )
            ],
            "shipping_amount" => $this->getPriceObject(
                $quote->getShippingAddress()->getBaseShippingAmount(),
                $quote->getBaseCurrencyCode()
            ),
            "tax_amount" => $this->getPriceObject(
                $quote->getShippingAddress()->getBaseTaxAmount(),
                $quote->getBaseCurrencyCode()
            ),
            "order_amount" => $this->getPriceObject($quote->getBaseGrandTotal(), $quote->getBaseCurrencyCode()),
            "locale" => $this->localeResolver->getLocale(),
        ];
        if ($this->config->isInContextModeActive()) {
            return array_merge($orderPayload, ['checkout_mode' => $this->config->getInContextMode()]);
        }
        return $orderPayload;
    }

    /**
     * Get Price Object
     *
     * @param float $amount
     * @param string $currency
     * @return array
     */
    private function getPriceObject($amount, $currency)
    {
        return [
            "amount_in_cents" => Util::formatToCents($amount),
            "currency" => $currency
        ];
    }

    /**
     * Build Customer Payload
     * @param Quote $quote
     * @return array
     */
    private function buildCustomerPayload($quote)
    {
        $billingAddress = $quote->getBillingAddress();
        try {
            $tokenize = !$this->config->isInContextModeActive() && $this->config->isTokenizationEnabled();
        } catch (InputException|NoSuchEntityException $e) {
            $tokenize = false;
        }

        return [
            "tokenize" => $tokenize,
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
     */
    private function buildItemPayload($quote)
    {
        $itemPayload = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $productName = $item->getName();
            $productSku = $item->getSku();
            $productQuantity = $item->getQty();
            $itemData = [
                "name" => $productName,
                "sku" => $productSku,
                "quantity" => $productQuantity,
                "price" => [
                    "amount_in_cents" => Util::formatToCents($item->getPriceInclTax()),
                    "currency" => $quote->getBaseCurrencyCode()
                ]
            ];
            $itemPayload[] = $itemData;
        }
        return $itemPayload;
    }
}
