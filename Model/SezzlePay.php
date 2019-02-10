<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 * @license     https://www.sezzle.com/LICENSE.txt
 */

namespace Sezzle\Sezzlepay\Model;

use Magento\Sales\Model\Order;

/**
 * Class SezzlePay
 * @package Sezzle\Sezzlepay\Model
 */
class SezzlePay extends \Magento\Payment\Model\Method\AbstractMethod
{
    const XML_PATH_PRIVATE_KEY = 'payment/sezzlepay/private_key';
    const XML_PATH_PUBLIC_KEY = 'payment/sezzlepay/public_key';
    const ADDITIONAL_INFORMATION_KEY_ORDERID = 'sezzle_order_id';
    const ADDITIONAL_INFORMATION_KEY_TOKENGENERATED = 'sezzlepay_token_generated';
    /**
     * @var string
     */
    protected $_code = 'sezzlepay';
    /**
     * @var bool
     */
    protected $_isGateway = true;
    /**
     * @var bool
     */
    protected $_isInitializeNeeded = false;
    /**
     * @var bool
     */
    protected $_canOrder = true;
    /**
     * @var bool
     */
    protected $_canAuthorize = true;
    /**
     * @var bool
     */
    protected $_canCapture = true;
    /**
     * @var bool
     */
    protected $_canRefund = true;
    /**
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;
    /**
     * @var bool
     */
    protected $_canUseInternal = false;
    /**
     * @var bool
     */
    protected $_canFetchTransactionInfo = true;

    /**
     * @var Api\PayloadBuilder
     */
    private $apiPayloadBuilder;
    /**
     * @var Api\ConfigInterface
     */
    private $sezzleApiConfig;
    /**
     * @var Config\Container\SezzleApiIdentity
     */
    private $sezzleApiIdentity;
    /**
     * @var Api\ProcessorInterface
     */
    private $sezzleApiProcessor;
    /**
     * @var Order\Payment\Transaction\BuilderInterface
     */
    private $_transactionBuilder;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;
    /**
     * @var
     */
    protected $_logger;

    /**
     * SezzlePay constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param Config\Container\SezzleApiIdentity $sezzleApiIdentity
     * @param Api\ConfigInterface $sezzleApiConfig
     * @param Api\PayloadBuilder $apiPayloadBuilder
     * @param Api\ProcessorInterface $sezzleApiProcessor
     * @param Order\Payment\Transaction\BuilderInterface $transactionBuilder
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $mageLogger
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        Config\Container\SezzleApiIdentity $sezzleApiIdentity,
        Api\ConfigInterface $sezzleApiConfig,
        Api\PayloadBuilder $apiPayloadBuilder,
        Api\ProcessorInterface $sezzleApiProcessor,
        Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $mageLogger
    )
    {
        $this->apiPayloadBuilder = $apiPayloadBuilder;
        $this->sezzleApiConfig = $sezzleApiConfig;
        $this->sezzleApiIdentity = $sezzleApiIdentity;
        $this->sezzleApiProcessor = $sezzleApiProcessor;
        $this->_transactionBuilder = $transactionBuilder;
        $this->jsonHelper = $jsonHelper;
        $this->_logger = $context->getLogger();
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

    /**
     * Get Sezzle checkout url
     * @param $quote
     * @return bool
     */
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

    /**
     * Get Sezzle redirect url
     * @param $quote
     * @param $reference
     * @return mixed
     */
    public function getSezzleRedirectUrl($quote, $reference)
    {
        $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . '/v1/checkouts';
        $requestBody = $this->apiPayloadBuilder->buildSezzleCheckoutPayload($quote, $reference);
        try {
            $response = $this->sezzleApiProcessor->call(
                $url,
                $requestBody,
                \Magento\Framework\HTTP\ZendClient::POST
            );
        } catch (\Exception $e) {
            $this->_logger->debug($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
        return $response;
    }

    /**
     * Capture payment
     * @param $reference
     * @return mixed
     */
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

    /**
     * Create refund
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @return $this
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $orderId = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDERID);
        if ($orderId) {
            $currency = $payment->getOrder()->getGlobalCurrencyCode();
            try {
                $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . '/v1/orders' . '/' . $orderId . '/refund';
                $this->sezzleApiProcessor->call(
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
     * Create transaction
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
}
