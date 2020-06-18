<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Model\Info;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Sezzle\Payment\Api\V2Interface;
use Sezzle\Payment\Helper\Data;
use Sezzle\Payment\Model\Api\PayloadBuilder;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class Sezzle
 * @package Sezzle\Payment\Model
 */
class Sezzle extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_CODE = 'sezzle';
    const ADDITIONAL_INFORMATION_KEY_REFERENCE_ID = 'sezzle_reference_id';
    const ADDITIONAL_INFORMATION_KEY_ORDER_UUID = 'sezzle_order_uuid';
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
    protected $_isInitializeNeeded = true;
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
    protected $_canCapturePartial = true;
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
     * @var Order\Payment\Transaction\BuilderInterface
     */
    private $_transactionBuilder;

    /**
     * @var Data
     */
    protected $sezzleHelper;

    /**
     * @var V2Interface
     */
    protected $v2;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var Config\Container\SezzleApiConfigInterface
     */
    private $sezzleApiConfig;
    /**
     * @var array
     */
    private $sezzleInformation = [];

    /**
     * @var Quote
     */
    public $quote;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var Tokenize
     */
    private $tokenizeModel;
    /**
     * @var DateTime
     */
    protected $dateTime;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Sezzle constructor.
     * @param Context $context
     * @param Config\Container\SezzleApiConfigInterface $sezzleApiConfig
     * @param Data $sezzleHelper
     * @param Order\Payment\Transaction\BuilderInterface $transactionBuilder
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $mageLogger
     * @param QuoteRepository $quoteRepository
     * @param V2Interface $v2
     * @param DateTime $dateTime
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        Context $context,
        Config\Container\SezzleApiConfigInterface $sezzleApiConfig,
        Data $sezzleHelper,
        Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $mageLogger,
        QuoteRepository $quoteRepository,
        V2Interface $v2,
        DateTime $dateTime,
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession
    ) {
        $this->sezzleHelper = $sezzleHelper;
        $this->sezzleApiConfig = $sezzleApiConfig;
        $this->_transactionBuilder = $transactionBuilder;
        $this->quoteRepository = $quoteRepository;
        $this->v2 = $v2;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->dateTime = $dateTime;
        $this->customerRepository = $customerRepository;
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
     * Initialize _sezzleInformation with $this->_data['additional_information'] if empty
     *
     * @return void
     */
    private function initSezzleInformation()
    {
        $sezzleInfo = $this->_getData('sezzle_information');
        if (empty($this->sezzleInformation) && $sezzleInfo) {
            $this->sezzleInformation = $sezzleInfo;
        }
    }

    /**
     * Sezzle information setter
     * Updates data inside the 'sezzle_information' array
     * or all 'sezzle_information' if key is data array
     *
     * @param string|array $key
     * @param mixed $value
     * @return Quote
     * @throws LocalizedException
     */
    public function setSezzleInformation($key, $value = null)
    {
        /** @var Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        if (is_object($value)) {
            throw new LocalizedException(__('The order disallows storing objects.'));
        }
        $this->initSezzleInformation();
        if (is_array($key) && $value === null) {
            $this->sezzleInformation = $key;
        } else {
            $this->sezzleInformation[$key] = $value;
        }
        $this->sezzleHelper->logSezzleActions($this->sezzleInformation);
        return $quote->setData('sezzle_information', $this->sezzleInformation);
    }

    /**
     * Getter for entire sezzle_information value or one of its element by key
     *
     * @param string $key
     * @return array|null|mixed
     */
    public function getSezzleInformation($key = null)
    {
        $this->initSezzleInformation();
        if (null === $key) {
            return $this->sezzleInformation;
        }
        return isset($this->sezzleInformation[$key]) ? $this->sezzleInformation[$key] : null;
    }

    /**
     * Unsetter for entire additional_information value or one of its element by key
     *
     * @param string $key
     * @return Quote|Sezzle
     */
    public function unsSezzleInformation($key = null)
    {
        /** @var Quote $quote */
        $quote = $this->checkoutSession->getQuote();
        if ($key && isset($this->sezzleInformation[$key])) {
            unset($this->sezzleInformation[$key]);
            return $this->quote->setData('sezzle_information', $this->sezzleInformation);
        } elseif (null === $key) {
            $this->sezzleInformation = [];
            return $quote->unsetData('sezzle_information');
        }

        return $this;
    }

    /**
     * Check whether there is sezzle information by specified key
     *
     * @param mixed|null $key
     * @return bool
     */
    public function hasSezzleInformation($key = null)
    {
        $this->initSezzleInformation();
        return null === $key ? !empty($this->sezzleInformation) : array_key_exists(
            $key,
            $this->sezzleInformation
        );
    }

    /**
     * Get Sezzle checkout url
     *
     * @param Quote $quote
     * @return string
     * @throws LocalizedException
     * @throws \Exception
     */
    public function getSezzleRedirectUrl($quote)
    {
        $referenceID = uniqid() . "-" . $quote->getReservedOrderId();
        $this->sezzleHelper->logSezzleActions("Reference Id : $referenceID");
        $payment = $quote->getPayment();
        $payment->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID, $referenceID);
        $session = $this->v2->createSession($referenceID);
        $redirectURL = '';
        if ($quote->getCustomer() && $this->isCustomerUUIDValid($quote)) {
            $this->setSezzleInformation(
                Tokenize::ATTR_SEZZLE_CUSTOMER_UUID,
                $quote->getCustomer()->getCustomAttribute(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID)->getValue()
            );
            $this->setSezzleInformation(
                Tokenize::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION,
                $quote->getCustomer()->getCustomAttribute(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION)->getValue()
            );
            $redirectURL = $this->sezzleApiConfig->getTokenizePaymentCompleteURL();
        } else {
            if ($session->getOrder()) {
                $redirectURL = $session->getOrder()->getCheckoutUrl();
                if ($session->getOrder()->getUuid()) {
                    $payment->setAdditionalInformation(
                        self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID,
                        $session->getOrder()->getUuid()
                    );
                }
                if (is_array($session->getOrder()->getLinks())) {
                    foreach ($session->getOrder()->getLinks() as $link) {
                        $this->setSezzleInformation($link->getRel(), $link->getHref());
                    }
                }
            }
            if ($session->getTokenize()) {
                $this->customerSession->setCustomerSezzleToken($session->getTokenize()->getToken());
                $this->customerSession->setCustomerSezzleTokenExpiration($session->getTokenize()->getExpiration());
                $this->customerSession->setCustomerSezzleTokenStatus(true);
            }
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
     * @param string $paymentAction
     * @param object $stateObject
     * @return Sezzle|void
     * @throws LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        switch ($paymentAction) {
            case self::ACTION_AUTHORIZE:
                $payment = $this->getInfoInstance();
                $order = $payment->getOrder();
                $order->setCanSendNewEmailFlag(false);
                $payment->authorize(true, $order->getBaseTotalDue()); // base amount will be set inside
                $payment->setAmountAuthorized($order->getTotalDue());
                $orderStatus = $payment->getMethodInstance()->getConfigData('order_status');
                $order->setState(Order::STATE_NEW, 'new', '', false);
                $stateObject->setState(Order::STATE_NEW);
                $stateObject->setStatus($orderStatus);
                break;
            case self::ACTION_AUTHORIZE_CAPTURE:
                $payment = $this->getInfoInstance();
                $order = $payment->getOrder();
                $order->setCanSendNewEmailFlag(false);
                $payment->capture(null);
                $payment->setAmountPaid($order->getTotalDue());
                $orderStatus = $payment->getMethodInstance()->getConfigData('order_status');
                $order->setState(Order::STATE_PROCESSING, 'processing', '', false);
                $stateObject->setState(Order::STATE_PROCESSING);
                $stateObject->setStatus($orderStatus);
                break;
            default:
                break;
        }
    }

    /**
     * Send authorize request to gateway
     *
     * @param DataObject|InfoInterface $payment
     * @param float $amount
     * @return Sezzle
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws LocalizedException
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new LocalizedException(__('The authorize action is not available.'));
        } elseif ($amount <= 0) {
            throw new LocalizedException(__('Invalid amount for authorize.'));
        } elseif (!$this->validateOrder($payment)) {
            throw new LocalizedException(__('Unable to validate the order.'));
        }
        $this->sezzleHelper->logSezzleActions("****Authorization start****");
        $reference = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID);

        $amountInCents = (int)(round($amount * 100, PayloadBuilder::PRECISION));
        $this->sezzleHelper->logSezzleActions("Sezzle Reference ID : $reference");
        if ($sezzleCustomerUUID = $payment->getAdditionalInformation(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID)) {
            $response = $this->v2->createOrderByCustomerUUID($sezzleCustomerUUID, $amountInCents);
            if ($orderUUID = $response->getUuid()) {
                $payment->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID, $orderUUID);
            }
        }
        $payment->setAdditionalInformation('payment_type', $this->getConfigPaymentAction());
        $payment->setTransactionId($reference)->setIsTransactionClosed(false);
        $this->sezzleHelper->logSezzleActions("Authorization successful");
        $this->sezzleHelper->logSezzleActions("Authorization end");
        return $this;
    }

    /**
     * Capture at Magento
     *
     * @param InfoInterface $payment
     * @param float $amount
     * @return Sezzle
     * @throws LocalizedException
     */
    public function capture(InfoInterface $payment, $amount)
    {
        $this->sezzleHelper->logSezzleActions("****Capture at Magento start****");
        if (!$this->canCapture()) {
            throw new LocalizedException(__('The capture action is not available.'));
        } elseif ($amount <= 0) {
            throw new LocalizedException(__('Invalid amount for capture.'));
        }
        $reference = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID);
        $sezzleOrderUUID = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID);
        $amountInCents = (int)(round($amount * 100, PayloadBuilder::PRECISION));
        $payment->setAdditionalInformation('payment_type', $this->getConfigPaymentAction());
        $orderTotalInCents = (int)(round(
            $payment->getOrder()->getBaseGrandTotal() * 100,
            PayloadBuilder::PRECISION
        ));
        if ($sezzleCustomerUUID = $payment->getAdditionalInformation(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID)) {
            $response = $this->v2->createOrderByCustomerUUID(
                $sezzleCustomerUUID,
                $amountInCents
            );
            $sezzleOrderUUID = $response->getUuid();
        }
        if (!$this->validateOrder($payment)) {
            throw new LocalizedException(__('Unable to validate the order.'));
        }
        $this->v2->captureByOrderUUID($sezzleOrderUUID, $amountInCents, $amountInCents < $orderTotalInCents);
        if (!$payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID)) {
            $payment->setAdditionalInformation(
                self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID,
                $sezzleOrderUUID
            );
        }
        $payment->setTransactionId($reference)->setIsTransactionClosed(true);
        $this->sezzleHelper->logSezzleActions("Authorized on Sezzle");
        $this->sezzleHelper->logSezzleActions("****Capture at Magento end****");
        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @return $this|Sezzle
     * @throws LocalizedException
     */
    public function void(InfoInterface $payment)
    {
        if (!$this->canVoid()) {
            throw new LocalizedException(__('The void action is not available.'));
        } elseif (!$this->validateOrder($payment)) {
            throw new LocalizedException(__('Unable to validate the order.'));
        }
        $amountInCents = (int)(round($payment->getOrder()->getBaseGrandTotal() * 100, PayloadBuilder::PRECISION));
        if ($orderUUID = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID)) {
            $this->v2->releasePaymentByOrderUUID($orderUUID, $amountInCents);
        } else {
            throw new LocalizedException(__('Failed to void the payment.'));
        }
        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this|Sezzle
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        if (!$this->canRefund()) {
            throw new LocalizedException(__('The refund action is not available.'));
        } elseif ($amount <= 0) {
            throw new LocalizedException(__('Invalid amount for refund.'));
        } elseif (!$this->validateOrder($payment)) {
            throw new LocalizedException(__('Unable to validate the order.'));
        }
        $amountInCents = (int)(round($amount * 100, PayloadBuilder::PRECISION));
        if ($sezzleOrderUUID = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID)) {
            $this->v2->refundByOrderUUID($sezzleOrderUUID, $amountInCents);
        } else {
            throw new LocalizedException(__('Failed to refund the payment.'));
        }
        return $this;
    }

    /**
     * Check whether payment method can be used
     *
     * @param CartInterface|null $quote
     * @return bool
     * @deprecated 100.2.0
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }

        $checkResult = new DataObject();
        $checkResult->setData('is_available', true);

        $merchantUUID = $this->sezzleApiConfig->getMerchantId();
        $publicKey = $this->sezzleApiConfig->getPublicKey();
        $privateKey = $this->sezzleApiConfig->getPrivateKey();
        $minCheckoutAmount = $this->sezzleApiConfig->getMinCheckoutAmount();

        if (($this->getCode() == self::PAYMENT_CODE)
            && ((!$merchantUUID || !$publicKey || !$privateKey)
                || ($quote && ($quote->getBaseGrandTotal() < $minCheckoutAmount)))) {
            $checkResult->setData('is_available', false);
        }

        return $checkResult->getData('is_available');
    }

    /**
     * Validate Magento stored Order UUID and Sezzle Order UUID
     *
     * @param InfoInterface $payment
     * @return bool
     * @throws LocalizedException
     */
    private function validateOrder($payment)
    {
        if ($sezzleOrderUUID = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID)) {
            $sezzleOrder = $this->v2->getOrder($sezzleOrderUUID);
            if ($sezzleOrderUUID != $sezzleOrder->getUuid()) {
                return false;
            }
            return true;
        }
        return false;
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
        $sezzleOrderUUID = $order->getPayment()->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID);
        $sezzleOrder = $this->v2->getOrder((string)$sezzleOrderUUID);
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
        $paymentAction = $this->getConfigPaymentAction();
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
