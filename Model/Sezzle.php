<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Sezzle\Payment\Api\V2Interface;
use Sezzle\Payment\Model\Api\PayloadBuilder;

/**
 * Class Sezzle
 * @package Sezzle\Payment\Model
 */
class Sezzle extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_CODE = 'sezzle';
    const ADDITIONAL_INFORMATION_KEY_REFERENCE_ID = 'sezzle_reference_id';
    const ADDITIONAL_INFORMATION_KEY_ORDER_UUID = 'sezzle_order_uuid';
    const SEZZLE_CAPTURE_EXPIRY = 'sezzle_capture_expiry';
    const SEZZLE_AUTH_EXPIRY = 'sezzle_auth_expiry';

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
    protected $_canVoid = true;
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
     * @var \Sezzle\Payment\Helper\Data
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
     * @var V2Interface
     */
    private $v2;
    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    private $quoteRepository;
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * Sezzle constructor.
     * @param Context $context
     * @param Config\Container\SezzleApiIdentity $sezzleApiIdentity
     * @param Api\ConfigInterface $sezzleApiConfig
     * @param \Sezzle\Payment\Helper\Data $sezzleHelper
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
     * @param V2Interface $v2
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context $context,
        Config\Container\SezzleApiIdentity $sezzleApiIdentity,
        Api\ConfigInterface $sezzleApiConfig,
        \Sezzle\Payment\Helper\Data $sezzleHelper,
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
        V2Interface $v2,
        CustomerSession $customerSession
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
        $this->customerSession = $customerSession;
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
        $payment->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID, $referenceID);
        $session = $this->v2->createSession($referenceID);
        if ($session->getOrder()) {
            $redirectURL = $session->getOrder()->getCheckoutUrl();
            if ($session->getOrder()->getUuid()) {
                $payment->setAdditionalInformation(
                    self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID,
                    $session->getOrder()->getUuid()
                );
            }
        } elseif ($session->getTokenize()) {
            $this->customerSession->setCustomerSezzleToken($session->getTokenize()->getToken());
            $this->customerSession->setCustomerSezzleTokenExpiration($session->getTokenize()->getExpiration());
            $this->customerSession->setCustomerSezzleTokenStatus('Approved');
            $redirectURL = $session->getTokenize()->getApprovalUrl();
        }
        $this->sezzleHelper->logSezzleActions("Redirect URL : $redirectURL");
        if (!$redirectURL) {
            $this->sezzleHelper->logSezzleActions("No Token response from API");
            throw new LocalizedException(__('There is an issue processing your order.'));
        }
        $this->quoteRepository->save($quote);
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
     * @param \Magento\Framework\DataObject|InfoInterface $payment
     * @param float $amount
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        $this->sezzleHelper->logSezzleActions("****Authorization start****");
        $reference = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID);
        $sezzleOrderUUID = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID);
        $amountInCents = (int)(round($amount * 100, PayloadBuilder::PRECISION));
        $this->sezzleHelper->logSezzleActions("Sezzle Reference ID : $reference");
        $this->sezzleHelper->logSezzleActions("Magento Order Total : $amountInCents");
        $sezzleOrder = $this->v2->getOrder($sezzleOrderUUID);
        if ($sezzleOrderUUID != $sezzleOrder->getUuid()) {
            $this->sezzleHelper->logSezzleActions("Unable to validate order");
            throw new LocalizedException(__('Unable to validate order.'));
        }
        $sezzleOrderTotal = $sezzleOrder->getOrderAmount()->getAmountInCents();
        $this->sezzleHelper->logSezzleActions("Sezzle Order Total : $sezzleOrderTotal");

        if ($sezzleOrderTotal != null
            && !$this->isOrderAmountMatched($amountInCents, $sezzleOrderTotal)) {
            $this->sezzleHelper->logSezzleActions("Sezzle gateway has rejected request due to invalid order total");
            throw new LocalizedException(__('Sezzle gateway has rejected request due to invalid order total.'));
        } else {
            $payment->setAdditionalInformation('payment_type', $this->getConfigData('payment_action'));
            $payment->setTransactionId($reference)->setIsTransactionClosed(false);
            $this->sezzleHelper->logSezzleActions("Authorization successful");
            $this->sezzleHelper->logSezzleActions("Authorization end");
        }
    }

    /**
     * Capture at Magento
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return void
     * @throws LocalizedException
     */
    public function capture(InfoInterface $payment, $amount)
    {
        $this->sezzleHelper->logSezzleActions("****Capture at Magento start****");
        if ($amount <= 0) {
            throw new LocalizedException(__('Invalid amount for capture.'));
        }
        $reference = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID);
        $sezzleOrderUUID = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID);
        $amountInCents = (int)(round($amount * 100, PayloadBuilder::PRECISION));
//        $this->sezzleHelper->logSezzleActions("Sezzle Reference ID : $reference");
//        $this->sezzleHelper->logSezzleActions("Magento Order Total : $grandTotalInCents");
//        $result = $this->getSezzleOrderInfo($reference);
//        $sezzleOrderTotal = isset($result['amount_in_cents']) ?
//                                $result['amount_in_cents'] :
//                                null;
//        $this->sezzleHelper->logSezzleActions("Sezzle Order Total : $sezzleOrderTotal");
//
//        if ($sezzleOrderTotal != null
//            && !$this->isOrderAmountMatched($grandTotalInCents, $sezzleOrderTotal)) {
//            $this->sezzleHelper->logSezzleActions("Sezzle gateway has rejected request due to invalid order total");
//            throw new LocalizedException(__('Sezzle gateway has rejected request due to invalid order total.'));
//        }

//        $captureExpiration = (isset($result['capture_expiration']) && $result['capture_expiration']) ? $result['capture_expiration'] : null;
//        if ($captureExpiration === null) {
//            $this->sezzleHelper->logSezzleActions("Not authorized on Sezzle");
//            throw new LocalizedException(__('Not authorized on Sezzle. Please try again.'));
//        }
//        $captureExpirationTimestamp = $this->dateTime->timestamp($captureExpiration);
//        $currentTimestamp = $this->dateTime->timestamp("now");
//        $this->sezzleHelper->logSezzleActions("Capture Expiration Timestamp : $captureExpirationTimestamp");
//        $this->sezzleHelper->logSezzleActions("Current Timestamp : $currentTimestamp");
//        if ($captureExpirationTimestamp >= $currentTimestamp) {
//            $payment->setAdditionalInformation('payment_type', $this->getConfigData('payment_action'));
//            $this->sezzleCapture($reference);
//            $this->v2->captureByOrderUUID($orderUUID, $grandTotalInCents, false);
//            $payment->setTransactionId($reference)->setIsTransactionClosed(false);
//            $this->sezzleHelper->logSezzleActions("Authorized on Sezzle");
//            $this->sezzleHelper->logSezzleActions("****Capture at Magento end****");
//        } else {
//            $this->sezzleHelper->logSezzleActions("Unable to capture amount. Time expired.");
//            throw new LocalizedException(__('Unable to capture amount.'));
//        }

        $payment->setAdditionalInformation('payment_type', $this->getConfigData('payment_action'));
        $response = $this->v2->captureByOrderUUID($sezzleOrderUUID, $amountInCents, false);
        if (!$response) {
            $this->sezzleHelper->logSezzleActions("Unable to capture amount.");
            throw new LocalizedException(__('Unable to capture amount.'));
        }
        $payment->setTransactionId($reference)->setIsTransactionClosed(true);
        $this->sezzleHelper->logSezzleActions("Authorized on Sezzle");
        $this->sezzleHelper->logSezzleActions("****Capture at Magento end****");
    }

    /**
     * Check whether payment method can be used
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     * @deprecated 100.2.0
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }

        $checkResult = new DataObject();
        $checkResult->setData('is_available', true);

        $merchantUUID = $this->sezzleApiIdentity->getMerchantId();
        $publicKey = $this->sezzleApiIdentity->getPublicKey();
        $privateKey = $this->sezzleApiIdentity->getPrivateKey();
        $minCheckoutAmount = $this->sezzleApiIdentity->getMinCheckoutAmount();

        if (($this->getCode() == self::PAYMENT_CODE)
            && ((!$merchantUUID || !$publicKey || !$privateKey)
                || ($quote && ($quote->getBaseGrandTotal() < $minCheckoutAmount)))) {
            $checkResult->setData('is_available', false);
        }

        return $checkResult->getData('is_available');
    }

    /**
     * Set Sezzle Auth Expiry
     *
     * @param OrderInterface $order
     * @return void
     * @throws LocalizedException
     */
    public function setSezzleAuthExpiry($order)
    {
        $sezzleOrderUUID = $order->getPayment()->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID);
        $sezzleOrder = $this->v2->getOrder($sezzleOrderUUID);
        if ($authExpiration = $sezzleOrder->getAuthorization()->getExpiration()) {
            $order->getPayment()->setAdditionalInformation(self::SEZZLE_AUTH_EXPIRY, $authExpiration)->save();
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
        $txnType = Transaction::TYPE_AUTH;
        $paymentAction = $this->getConfigData('payment_action');
        if ($paymentAction == self::ACTION_AUTHORIZE_CAPTURE) {
            $txnType = Transaction::TYPE_CAPTURE;
        }
        $transaction = $this->_transactionBuilder->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($reference)
            ->setFailSafe(true)
            ->build($txnType);

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
