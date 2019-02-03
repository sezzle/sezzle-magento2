<?php
/**
 * Created by PhpStorm.
 * User: arijit
 * Date: 1/31/2019
 * Time: 12:15 AM
 */

namespace Sezzle\Sezzlepay\Model\Api;

use Magento\Store\Model\StoreManagerInterface;

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
     * @param ConfigInterface $sezzleApiConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ConfigInterface $sezzleApiConfig,
        StoreManagerInterface $storeManager
    )
    {
        $this->sezzleApiConfig = $sezzleApiConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Build Sezzle Checkout Payload
     * @param $quote
     * @param $reference
     * @return array
     */
    public function buildSezzleCheckoutPayload($quote, $reference)
    {
        $checkoutPayload = $this->buildCheckoutPayload($quote, $reference);
        $customerPayload = $this->buildCustomerPayload($quote);
        $billingPayload = $this->buildBillingPayload($quote);
        $shippingPayload = $this->buildShippingPayload($quote);
        $itemPayload = $this->buildItemPayload($quote);
        $payload = array_merge(
            $checkoutPayload,
            $customerPayload,
            $billingPayload,
            $shippingPayload,
            $itemPayload);
        $payload["merchant_completes"] = true;
        return $payload;
    }

    /**
     * Build Checkout Payload from Magento Checkout
     * @param $quote
     * @param $reference
     * @return mixed
     */
    private function buildCheckoutPayload($quote, $reference)
    {
        $orderId = $quote->getReservedOrderId();
        $completeUrl = $this->sezzleApiConfig->getCompleteUrl($orderId, $reference);
        $cancelUrl = $this->sezzleApiConfig->getCancelUrl();
        $checkoutPayload["amount_in_cents"] = (int)(round($quote->getGrandTotal() * 100, self::PRECISION));
        $checkoutPayload["currency_code"] = $this->storeManager->getStore()->getCurrentCurrencyCode();
        $checkoutPayload["order_description"] = $reference;
        $checkoutPayload["order_reference_id"] = $reference;
        $checkoutPayload["display_order_reference_id"] = $orderId;
        $checkoutPayload["checkout_cancel_url"] = $cancelUrl;
        $checkoutPayload["checkout_complete_url"] = $completeUrl;
        return $checkoutPayload;
    }

    /**
     * Build Customer Payload
     * @param $quote
     * @return mixed
     */
    private function buildCustomerPayload($quote)
    {
        $billingAddress = $quote->getBillingAddress();
        $customerPayload["customer_details"] = [
            "first_name" => $quote->getCustomerFirstname() ? $quote->getCustomerFirstname() : $billingAddress->getFirstname(),
            "last_name" => $quote->getCustomerLastname() ? $quote->getCustomerLastname() : $billingAddress->getLastname(),
            "email" => $quote->getCustomerEmail(),
            "phone" => $billingAddress->getTelephone()
        ];
        return $customerPayload;
    }

    /**
     * Build Billing Address Payload
     * @param $quote
     * @return mixed
     */
    private function buildBillingPayload($quote)
    {
        $billingAddress = $quote->getBillingAddress();
        $billingPayload["billing_address"] = [
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
     * @param $quote
     * @return mixed
     */
    private function buildShippingPayload($quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        $shippingPayload["shipping_address"] = [
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
     * @param $quote
     * @return mixed
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