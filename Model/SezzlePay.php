<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Sezzle\Sezzlepay\Model\Api\V2;

/**
 * Class SezzlePay
 * @package Sezzle\Sezzlepay\Model
 */
class SezzlePay extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_CODE = 'sezzlepay';
    const ADDITIONAL_INFORMATION_KEY_ORDERID = 'sezzle_order_id';
    const SEZZLE_CAPTURE_EXPIRY = 'sezzle_capture_expiry';

    /**
     * @var string
     */
    protected $_code = self::PAYMENT_CODE;
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
     * @var \Sezzle\Sezzlepay\Helper\Data
     */
    protected $sezzleHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var Api\V2
     */
    private $v2;
    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    private $quoteRepository;

    /**
     * SezzlePay constructor.
     * @param Context $context
     * @param Config\Container\SezzleApiIdentity $sezzleApiIdentity
     * @param Api\ConfigInterface $sezzleApiConfig
     * @param \Sezzle\Sezzlepay\Helper\Data $sezzleHelper
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
     * @param CheckoutSession $checkoutSession
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param V2 $v2
     */
    public function __construct(
        Context $context,
        Config\Container\SezzleApiIdentity $sezzleApiIdentity,
        Api\ConfigInterface $sezzleApiConfig,
        \Sezzle\Sezzlepay\Helper\Data $sezzleHelper,
        Api\PayloadBuilder $apiPayloadBuilder,
        Api\ProcessorInterface $sezzleApiProcessor,
        Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $mageLogger,
        CheckoutSession $checkoutSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        V2 $v2
    ) {
        $this->apiPayloadBuilder = $apiPayloadBuilder;
        $this->sezzleHelper = $sezzleHelper;
        $this->sezzleApiConfig = $sezzleApiConfig;
        $this->sezzleApiIdentity = $sezzleApiIdentity;
        $this->sezzleApiProcessor = $sezzleApiProcessor;
        $this->_transactionBuilder = $transactionBuilder;
        $this->jsonHelper = $jsonHelper;
        $this->messageManager = $messageManager;
        $this->dateTime = $dateTime;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->v2 = $v2;
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
     * @param Quote $quote
     * @return string
     * @throws LocalizedException
     */
    public function getSezzleRedirectUrl($quote)
    {
        $referenceID = uniqid() . "-" . $quote->getReservedOrderId();
        $this->sezzleHelper->logSezzleActions("Reference Id : $referenceID");
        $payment = $quote->getPayment();
        $payment->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDERID, $referenceID);
        $this->quoteRepository->save($quote);
        $session = $this->v2->createSession($referenceID);
        if ($session->getOrder()) {
            $redirectURL = $session->getOrder()->getCheckoutUrl();
        } else {
            $redirectURL = $session->getTokenize()->getApprovalUrl();
        }
        $this->sezzleHelper->logSezzleActions("Redirect URL : $redirectURL");
        if (!$redirectURL) {
            $this->sezzleHelper->logSezzleActions("No Token response from API");
            throw new LocalizedException(__('There is an issue processing your order.'));
        }
        return $redirectURL;
    }

    /**
     * Check if order total is matching
     *
     * @param float $magentoAmount
     * @param float $sezzleAmount
     * @return bool
     */
    public function isOrderAmountMatched($magentoAmount, $sezzleAmount)
    {
        return (round($magentoAmount, 2) == round($sezzleAmount, 2)) ? true : false;
    }

    /**
     * Send authorize request to gateway
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->sezzleHelper->logSezzleActions("****Authorization start****");
        $reference = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDERID);
        $grandTotalInCents = (int)(round($amount * 100, \Sezzle\Sezzlepay\Model\Api\PayloadBuilder::PRECISION));
        $this->sezzleHelper->logSezzleActions("Sezzle Reference ID : $reference");
        $this->sezzleHelper->logSezzleActions("Magento Order Total : $grandTotalInCents");
        $result = $this->getSezzleOrderInfo($reference);
        $sezzleOrderTotal = isset($result['amount_in_cents']) ?
                                $result['amount_in_cents'] :
                                null;
        $this->sezzleHelper->logSezzleActions("Sezzle Order Total : $sezzleOrderTotal");

        if ($sezzleOrderTotal != null
        && !$this->isOrderAmountMatched($grandTotalInCents, $sezzleOrderTotal)) {
            $this->sezzleHelper->logSezzleActions("Sezzle gateway has rejected request due to invalid order total");
            throw new LocalizedException(__('Sezzle gateway has rejected request due to invalid order total.'));
        } else {
            $payment->setAdditionalInformation('payment_type', $this->getConfigData('payment_action'));
            $this->sezzleHelper->logSezzleActions("Authorization successful");
            $this->sezzleHelper->logSezzleActions("Authorization end");
        }
    }

    /**
     * Capture at Magento
     *
     * @param \Magento\Framework\DataObject|\Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->sezzleHelper->logSezzleActions("****Capture at Magento start****");
        if ($amount <= 0) {
            throw new LocalizedException(__('Invalid amount for capture.'));
        }
        $reference = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDERID);
        $grandTotalInCents = (int)(round($amount * 100, \Sezzle\Sezzlepay\Model\Api\PayloadBuilder::PRECISION));
        $this->sezzleHelper->logSezzleActions("Sezzle Reference ID : $reference");
        $this->sezzleHelper->logSezzleActions("Magento Order Total : $grandTotalInCents");
        $result = $this->getSezzleOrderInfo($reference);
        $sezzleOrderTotal = isset($result['amount_in_cents']) ?
                                $result['amount_in_cents'] :
                                null;
        $this->sezzleHelper->logSezzleActions("Sezzle Order Total : $sezzleOrderTotal");

        if ($sezzleOrderTotal != null
            && !$this->isOrderAmountMatched($grandTotalInCents, $sezzleOrderTotal)) {
            $this->sezzleHelper->logSezzleActions("Sezzle gateway has rejected request due to invalid order total");
            throw new LocalizedException(__('Sezzle gateway has rejected request due to invalid order total.'));
        }

        $captureExpiration = (isset($result['capture_expiration']) && $result['capture_expiration']) ? $result['capture_expiration'] : null;
        if ($captureExpiration === null) {
            $this->sezzleHelper->logSezzleActions("Not authorized on Sezzle");
            throw new LocalizedException(__('Not authorized on Sezzle. Please try again.'));
        }
        $captureExpirationTimestamp = $this->dateTime->timestamp($captureExpiration);
        $currentTimestamp = $this->dateTime->timestamp("now");
        $this->sezzleHelper->logSezzleActions("Capture Expiration Timestamp : $captureExpirationTimestamp");
        $this->sezzleHelper->logSezzleActions("Current Timestamp : $currentTimestamp");
        if ($captureExpirationTimestamp >= $currentTimestamp) {
            $payment->setAdditionalInformation('payment_type', $this->getConfigData('payment_action'));
            $this->sezzleCapture($reference);
            $payment->setTransactionId($reference)->setIsTransactionClosed(false);
            $this->sezzleHelper->logSezzleActions("Authorized on Sezzle");
            $this->sezzleHelper->logSezzleActions("****Capture at Magento end****");
        } else {
            $this->sezzleHelper->logSezzleActions("Unable to capture amount. Time expired.");
            throw new LocalizedException(__('Unable to capture amount.'));
        }
    }

    /**
     * Set Sezzle Capture Expiry
     *
     * @param string $reference
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface $payment
     * @return void
     * @throws LocalizedException
     */
    public function setSezzleCaptureExpiry($reference, $payment)
    {
        $sezzleOrder = $this->getSezzleOrderInfo($reference);
        if (isset($sezzleOrder['capture_expiration']) && $sezzleOrder['capture_expiration']) {
            $payment->setAdditionalInformation(self::SEZZLE_CAPTURE_EXPIRY, $sezzleOrder['capture_expiration']);
            $payment->save();
        }
    }

    /**
     * Get order info from Sezzle
     *
     * @param string $reference
     * @throws LocalizedException
     * @return array
     */
    public function getSezzleOrderInfo($reference)
    {
        $this->sezzleHelper->logSezzleActions("****Getting order from Sezzle****");
        $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . '/v1/orders' . '/' . $reference;
        $authToken = $this->sezzleApiConfig->getAuthToken();
        $result = $this->sezzleApiProcessor->call(
            $url,
            $authToken,
            null,
            \Magento\Framework\HTTP\ZendClient::GET
        );
        $result = $this->jsonHelper->jsonDecode($result, true);
        if (isset($result['status']) && $result['status'] == \Sezzle\Sezzlepay\Model\Api\ProcessorInterface::BAD_REQUEST) {
            throw new LocalizedException(__('Invalid checkout. Please retry again.'));
            return $this;
        }
        $this->sezzleHelper->logSezzleActions("****Order successfully fetched from Sezzle****");
        return $result;
    }

    /**
     * Capture payment at Sezzle
     *
     * @param $reference
     * @return mixed
     * @throws LocalizedException
     */
    public function sezzleCapture($reference)
    {
        try {
            $this->sezzleHelper->logSezzleActions("****Capture at Sezzle Start****");
            $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . '/v1/checkouts' . '/' . $reference . '/complete';
            $authToken = $this->sezzleApiConfig->getAuthToken();
            $response = $this->sezzleApiProcessor->call(
                $url,
                $authToken,
                null,
                \Magento\Framework\HTTP\ZendClient::POST
            );
            $this->sezzleHelper->logSezzleActions("****Capture at Sezzle End****");
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }
        return $response;
    }

    /**
     * Create refund
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @return $this
     * @throws LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->sezzleHelper->logSezzleActions("****Refund Start****");
        $orderId = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDERID);
        $this->sezzleHelper->logSezzleActions("Order Id : $orderId");
        if ($orderId) {
            $currency = $payment->getOrder()->getGlobalCurrencyCode();
            $this->sezzleHelper->logSezzleActions("Currency : $currency");
            try {
                $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . '/v1/orders' . '/' . $orderId . '/refund';
                $authToken = $this->sezzleApiConfig->getAuthToken();
                $requestPayload = ["amount" => [
                    "amount_in_cents" => (int)(round($amount * 100, \Sezzle\Sezzlepay\Model\Api\PayloadBuilder::PRECISION)),
                    "currency" => $currency
                ]
                ];
                $this->sezzleApiProcessor->call(
                    $url,
                    $authToken,
                    $requestPayload,
                    \Magento\Framework\HTTP\ZendClient::POST
                );
                $this->sezzleHelper->logSezzleActions("****Refund End****");
                return $this;
            } catch (\Exception $e) {
                $this->sezzleHelper->logSezzleActions($e->getMessage());
                throw new LocalizedException(__($e->getMessage()));
            }
        } else {
            $message = __('There is no Sezzle payment linked to this order. Please use refund offline for this order.');
            throw new LocalizedException($message);
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
        $this->sezzleHelper->logSezzleActions("****Transaction start****");
        $this->sezzleHelper->logSezzleActions("Order Id : " . $order->getId());
        $this->sezzleHelper->logSezzleActions("Reference Id : $reference");
        $payment = $order->getPayment();
        $payment->setLastTransId($reference);
        $payment->setTransactionId($reference);
        $formattedPrice = $order->getBaseCurrency()->formatTxt(
            $order->getGrandTotal()
        );
        $message = __('The authorized amount is %1.', $formattedPrice);
        $this->sezzleHelper->logSezzleActions($message);
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
        $this->sezzleHelper->logSezzleActions("Transaction Id : $transactionId");
        $this->sezzleHelper->logSezzleActions("****Transaction End****");
        return $transactionId;
    }
}
