<?php

namespace Sezzle\Sezzlepay\Model;

use Magento\Sales\Model\Order;

class SezzlePay extends \Magento\Payment\Model\Method\AbstractMethod
{
    const XML_PATH_PRIVATE_KEY = 'payment/sezzlepay/private_key';
    const XML_PATH_PUBLIC_KEY = 'payment/sezzlepay/public_key';
    const ADDITIONAL_INFORMATION_KEY_ORDERID = 'sezzle_order_id';
    const ADDITIONAL_INFORMATION_KEY_TOKENGENERATED = 'sezzlepay_token_generated';
    protected $_code = 'sezzlepay';
    protected $_isGateway = true;
    protected $_isInitializeNeeded = false;
    protected $_canOrder = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canUseInternal = false;
    protected $_canFetchTransactionInfo = true;
    protected $_storeManager;
    protected $_logger;
    protected $_scopeConfig;
    protected $_urlBuilder;
    protected $_sezzleApi;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        Config\Container\SezzleApiIdentity $sezzleApiIdentity,
        Api\ConfigInterface $sezzleApiConfig,
        Api\ProcessorInterface $sezzleApiProcessor,
        Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $mageLogger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Sezzle\Sezzlepay\Model\Api $sezzleApi
    )
    {
        $this->_storeManager = $storeManager;
        $this->sezzleApiConfig = $sezzleApiConfig;
        $this->sezzleApiIdentity = $sezzleApiIdentity;
        $this->sezzleApiProcessor = $sezzleApiProcessor;
        $this->_transactionBuilder = $transactionBuilder;
        $this->jsonHelper = $jsonHelper;
        $this->_logger = $context->getLogger();
        $this->_scopeConfig = $scopeConfig;
        $this->_urlBuilder = $urlBuilder;
        $this->_sezzleApi = $sezzleApi;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $mageLogger
        );
    }

    public function getSezzleCheckoutUrl($quote)
    {
        $reference = uniqid() . "-" . $quote->getReservedOrderId();
        $payment = $quote->getPayment();
        $payment->setAdditionalInformation(\Sezzle\Sezzlepay\Model\SezzlePay::ADDITIONAL_INFORMATION_KEY_ORDERID, $reference);
        $payment->save();
        $response = $this->getSezzleRedirectUrl($quote, $reference);
        $result = $this->jsonHelper->jsonDecode($response->getBody(), true);
        $orderUrl = array_key_exists('checkout_url', $result) ? $result['checkout_url'] : false;
        if (!$orderUrl) {
            $this->logger->info("No Token response from API");
            throw new \Magento\Framework\Exception\LocalizedException(__('There is an issue processing your order.'));
        }
        return $orderUrl;
    }

    public function getSezzleRedirectUrl($quote, $reference)
    {
        $precision = 2;
        $orderId = $quote->getReservedOrderId();
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();
        $completeUrl = $this->_urlBuilder->getUrl("sezzlepay/standard/complete/id/$orderId/magento_sezzle_id/$reference", ['_secure' => true]);
        $cancelUrl = $this->_urlBuilder->getUrl("sezzlepay/standard/cancel", ['_secure' => true]);

        $requestBody = [];
        $requestBody["amount_in_cents"] = (int)(round($quote->getGrandTotal() * 100, $precision));
        $requestBody["currency_code"] = $this->getStoreCurrencyCode();
        $requestBody["order_description"] = $reference;
        $requestBody["order_reference_id"] = $reference;
        $requestBody["display_order_reference_id"] = $orderId;
        $requestBody["checkout_cancel_url"] = $cancelUrl;
        $requestBody["checkout_complete_url"] = $completeUrl;
        $requestBody["customer_details"] = [
            "first_name" => $quote->getCustomerFirstname() ? $quote->getCustomerFirstname() : $billingAddress->getFirstname(),
            "last_name" => $quote->getCustomerLastname() ? $quote->getCustomerLastname() : $billingAddress->getLastname(),
            "email" => $quote->getCustomerEmail(),
            "phone" => $billingAddress->getTelephone()
        ];
        $requestBody["billing_address"] = [
            "street" => $billingAddress->getStreetLine(1),
            "street2" => $billingAddress->getStreetLine(2),
            "city" => $billingAddress->getCity(),
            "state" => $billingAddress->getRegionCode(),
            "postal_code" => $billingAddress->getPostcode(),
            "country_code" => $billingAddress->getCountryId(),
            "phone" => $billingAddress->getTelephone()
        ];
        $requestBody["shipping_address"] = [
            "street" => $shippingAddress->getStreetLine(1),
            "street2" => $shippingAddress->getStreetLine(2),
            "city" => $shippingAddress->getCity(),
            "state" => $shippingAddress->getRegionCode(),
            "postal_code" => $shippingAddress->getPostcode(),
            "country_code" => $shippingAddress->getCountryId(),
            "phone" => $shippingAddress->getTelephone()
        ];
        $requestBody["items"] = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $productName = $item->getName();
            $productSKU = $item->getSku();
            $productQuantity = $item->getQtyOrdered();
            $itemData = [
                "name" => $productName,
                "sku" => $productSKU,
                "quantity" => $productQuantity,
                "price" => [
                    "amount_in_cents" => (int)(round($item->getPriceInclTax() * 100, $precision)),
                    "currency" => $this->getStoreCurrencyCode()
                ]
            ];
            array_push($requestBody["items"], $itemData);
        }

        $requestBody["merchant_completes"] = true;

        try {
            $response = $this->_sezzleApi->call(
                $this->getSezzleAPIURL() . '/v1/checkouts',
                $requestBody,
                \Magento\Framework\HTTP\ZendClient::POST
            );
        } catch (\Exception $e) {
            $this->_logger->debug($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
        return $response;
    }

    protected function getStoreCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    protected function getSezzleAPIURL()
    {
        return $this->_scopeConfig->getValue('payment/sezzlepay/base_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function capturePayment($reference)
    {
        try {
            $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . '/v1/checkouts' . '/' . $reference . '/complete';
            $response = $this->sezzleApiProcessor->call(
                $url,
                null,
                \Magento\Framework\HTTP\ZendClient::POST
            );
        } catch (\Exception $e) {
            $this->_logger->debug($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
        return $response;
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $orderId = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDERID);
        if ($orderId) {
            $currency = $payment->getOrder()->getGlobalCurrencyCode();
            try {
                $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . '/v1/orders' . '/' . $orderId . '/refund';
                $response = $this->sezzleApiProcessor->call(
                    $url,
                    ["amount" => [
                        "amount_in_cents" => $amount * 100,
                        "currency" => $currency
                    ]
                    ],
                    \Magento\Framework\HTTP\ZendClient::POST
                );
                return $this;
            } catch (\Exception $e) {
                $this->_logger->debug($e->getMessage());
                throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            }
        } else {
            $message = __('There are no Sezzlepay payment linked to this order. Please use refund offline for this order.');
            throw new \Magento\Framework\Exception\LocalizedException($message);
        }
    }

    /**
     * @param $order
     * @param $reference
     * @return mixed
     */
    public function createTransaction($order, $reference)
    {
        $payment = $order->getPayment();
        $payment->setLastTransId($reference);
        $payment->setTransactionId($reference);
        $formattedPrice = $order->getBaseCurrency()->formatTxt(
            $order->getGrandTotal()
        );
        $message = __('The authorized amount is %1.', $formattedPrice);
        $transaction = $this->_transactionBuilder->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($reference)
            ->setFailSafe(true)
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

        $payment->addTransactionCommentsToOrder(
            $transaction,
            $message
        );
        $payment->setParentTransactionId(null);
        $payment->save();
        $order->save();
        $transactionId = $transaction->save()->getTransactionId();
        return $transactionId;
    }

    protected function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    protected function getStoreCode()
    {
        return $this->_storeManager->getStore()->getCode();
    }


}
