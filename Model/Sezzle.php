<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model;

use Exception;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Sezzle\Payment\Api\V2Interface;
use Sezzle\Payment\Helper\Data;
use Sezzle\Payment\Model\Api\PayloadBuilder;

/**
 * Class Sezzle
 * @package Sezzle\Payment\Model
 */
class Sezzle extends AbstractMethod
{
    const PAYMENT_CODE = 'sezzle';
    const ADDITIONAL_INFORMATION_KEY_REFERENCE_ID = 'sezzle_reference_id';
    const ADDITIONAL_INFORMATION_KEY_ORDER_UUID = 'sezzle_order_uuid';
    const SEZZLE_AUTH_EXPIRY = 'sezzle_auth_expiry';

    const ADDITIONAL_INFORMATION_KEY_AUTH_AMOUNT = 'sezzle_auth_amount';
    const ADDITIONAL_INFORMATION_KEY_CAPTURE_AMOUNT = 'sezzle_capture_amount';
    const ADDITIONAL_INFORMATION_KEY_REFUND_AMOUNT = 'sezzle_refund_amount';
    const ADDITIONAL_INFORMATION_KEY_RELEASE_AMOUNT = 'sezzle_order_amount';

    const ADDITIONAL_INFORMATION_KEY_GET_ORDER_LINK = 'sezzle_get_order_link';
    const ADDITIONAL_INFORMATION_KEY_CAPTURE_LINK = 'sezzle_capture_link';
    const ADDITIONAL_INFORMATION_KEY_REFUND_LINK = 'sezzle_refund_link';
    const ADDITIONAL_INFORMATION_KEY_RELEASE_LINK = 'sezzle_release_link';
    const ADDITIONAL_INFORMATION_KEY_CREATE_ORDER_LINK = 'sezzle_create_order_link';
    const ADDITIONAL_INFORMATION_KEY_GET_TOKEN_DETAILS_LINK = 'sezzle_token_link';

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
     * @var System\Config\Container\SezzleApiConfigInterface
     */
    private $sezzleApiConfig;
    /**
     * @var Tokenize
     */
    private $tokenizeModel;

    /**
     * Sezzle constructor.
     * @param Context $context
     * @param System\Config\Container\SezzleApiConfigInterface $sezzleApiConfig
     * @param Data $sezzleHelper
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $mageLogger
     * @param QuoteRepository $quoteRepository
     * @param V2Interface $v2
     * @param CustomerSession $customerSession
     * @param Tokenize $tokenizeModel
     */
    public function __construct(
        Context $context,
        System\Config\Container\SezzleApiConfigInterface $sezzleApiConfig,
        Data $sezzleHelper,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $mageLogger,
        QuoteRepository $quoteRepository,
        V2Interface $v2,
        CustomerSession $customerSession,
        Tokenize $tokenizeModel
    ) {
        $this->sezzleHelper = $sezzleHelper;
        $this->sezzleApiConfig = $sezzleApiConfig;
        $this->quoteRepository = $quoteRepository;
        $this->v2 = $v2;
        $this->customerSession = $customerSession;
        $this->tokenizeModel = $tokenizeModel;
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
     *
     * @param Quote $quote
     * @return string
     * @throws LocalizedException
     * @throws Exception
     */
    public function getSezzleRedirectUrl($quote)
    {
        $referenceID = uniqid() . "-" . $quote->getReservedOrderId();
        $this->sezzleHelper->logSezzleActions("Reference Id : $referenceID");
        $this->sezzleHelper->logSezzleActions("Payment Type : " . $this->getConfigPaymentAction());
        $payment = $quote->getPayment();
        $additionalInformation[self::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID] = $referenceID;
        $redirectURL = '';
        if ($quote->getCustomer() && $this->tokenizeModel->isCustomerUUIDValid($quote)) {
            $this->sezzleHelper->logSezzleActions("Tokenized Checkout");
            $tokenizeInformation = [
                Tokenize::ATTR_SEZZLE_CUSTOMER_UUID => $quote->getCustomer()->getCustomAttribute(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID)->getValue(),
                Tokenize::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION => $quote->getCustomer()->getCustomAttribute(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION)->getValue(),
                self::ADDITIONAL_INFORMATION_KEY_CREATE_ORDER_LINK => $quote->getCustomer()->getCustomAttribute(self::ADDITIONAL_INFORMATION_KEY_CREATE_ORDER_LINK)->getValue(),
            ];
            $additionalInformation = array_merge($additionalInformation, $tokenizeInformation);
            $redirectURL = $this->sezzleApiConfig->getTokenizePaymentCompleteURL();
        } else {
            $this->sezzleHelper->logSezzleActions("Typical Checkout");
            $session = $this->v2->createSession($referenceID);
            if ($session->getOrder()) {
                $redirectURL = $session->getOrder()->getCheckoutUrl();
                if ($session->getOrder()->getUuid()) {
                    $orderUUID = [
                        self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID => $session->getOrder()->getUuid()
                    ];
                    $additionalInformation = array_merge($additionalInformation, $orderUUID);
                }
                $links = [];
                if (is_array($session->getOrder()->getLinks())) {
                    foreach ($session->getOrder()->getLinks() as $link) {
                        $rel = "sezzle_" . $link->getRel() . "_link";
                        if ($link->getMethod() == 'GET' && strpos($rel, "self") !== false) {
                            $rel = self::ADDITIONAL_INFORMATION_KEY_GET_ORDER_LINK;
                        }
                        $links[$rel] = $link->getHref();
                    }
                    $additionalInformation = array_merge($additionalInformation, $links);
                }
            }
            if ($session->getTokenize()) {
                $this->customerSession->setCustomerSezzleToken($session->getTokenize()->getToken());
                $this->customerSession->setCustomerSezzleTokenExpiration($session->getTokenize()->getExpiration());
                $this->customerSession->setCustomerSezzleTokenStatus(true);
            }
            if (is_array($session->getTokenize()->getLinks())) {
                foreach ($session->getTokenize()->getLinks() as $link) {
                    if ($link->getRel() == self::ADDITIONAL_INFORMATION_KEY_GET_TOKEN_DETAILS_LINK) {
                        $this->customerSession->setGetTokenDetailsLink($link->getHref());
                    }
                }
            }
        }
        if (!$redirectURL) {
            $this->sezzleHelper->logSezzleActions("Redirect URL was not received from Sezzle.");
            throw new LocalizedException(__('Unable to start your checkout with Sezzle.'));
        }
        $payment->setAdditionalInformation($additionalInformation);
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
                /** @var Order $order */
                $order = $payment->getOrder();
                $order->setCanSendNewEmailFlag(false);
                $payment->authorize(true, $order->getBaseTotalDue()); // base amount will be set inside
                $payment->setAmountAuthorized($order->getTotalDue());
                $orderStatus = $order->getConfig()->getStateDefaultStatus(Order::STATE_NEW);
                $order->setCustomerNote("Payment authorized by Sezzle.");
                $stateObject->setState(Order::STATE_NEW);
                $stateObject->setStatus($orderStatus);
                $stateObject->setIsNotified(true);
                break;
            case self::ACTION_AUTHORIZE_CAPTURE:
                $payment = $this->getInfoInstance();
                /** @var Order $order */
                $order = $payment->getOrder();
                $order->setCanSendNewEmailFlag(false);
                $payment->capture(null);
                $payment->setAmountPaid($order->getTotalDue());
                $orderStatus = $order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING);
                $order->setCustomerNote("Payment captured by Sezzle.");
                $stateObject->setState(Order::STATE_PROCESSING);
                $stateObject->setStatus($orderStatus);
                $stateObject->setIsNotified(true);
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
        }
        $this->sezzleHelper->logSezzleActions("****Authorization start****");
        $reference = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID);

        $amountInCents = (int)(round($amount * 100, PayloadBuilder::PRECISION));
        $this->sezzleHelper->logSezzleActions("Sezzle Reference ID : $reference");
        $orderUUID = "";
        if ($sezzleCustomerUUID = $payment->getAdditionalInformation(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID)) {
            $url = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_CREATE_ORDER_LINK);
            $response = $this->v2->createOrderByCustomerUUID($url, $sezzleCustomerUUID, $amountInCents);
            if ($orderUUID = $response->getUuid()) {
                $payment->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID, $orderUUID);
            }
            if (is_array($response->getLinks())) {
                foreach ($response->getLinks() as $link) {
                    $rel = "sezzle_" . $link->getRel() . "_link";
                    if ($link->getMethod() == 'GET' && strpos($rel, "self") !== false) {
                        $rel = self::ADDITIONAL_INFORMATION_KEY_GET_ORDER_LINK;
                    }
                    $payment->setAdditionalInformation($rel, $link->getHref());
                }
            }
        }
        if (!$this->validateOrder($payment)) {
            throw new LocalizedException(__('Unable to validate the order.'));
        }
        $this->sezzleHelper->logSezzleActions("Order validated at Sezzle");
        $this->sezzleHelper->logSezzleActions("Order UUID : $orderUUID");
        $authorizedAmount = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_AUTH_AMOUNT);
        $authorizedAmount += $amount;
        $payment->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_AUTH_AMOUNT, $authorizedAmount);
        $payment->setAdditionalInformation('payment_type', $this->getConfigPaymentAction());
        $payment->setTransactionId($reference)->setIsTransactionClosed(false);
        $this->sezzleHelper->logSezzleActions("Transaction ID : $reference");
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
            $sezzleOrderUUID = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID);
            if (!$sezzleOrderUUID) {
                $url = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_CREATE_ORDER_LINK);
                $response = $this->v2->createOrderByCustomerUUID(
                    $url,
                    $sezzleCustomerUUID,
                    $amountInCents
                );
                $sezzleOrderUUID = $response->getUuid();
                $payment->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID, $sezzleOrderUUID);
                if (is_array($response->getLinks())) {
                    foreach ($response->getLinks() as $link) {
                        $rel = $link->getRel();
                        if ($link->getMethod() == 'GET' && $link->getRel() == 'self') {
                            $rel = self::ADDITIONAL_INFORMATION_KEY_GET_ORDER_LINK;
                        }
                        $payment->setAdditionalInformation($rel, $link->getHref());
                    }
                }
            }
        }
        if (!$this->validateOrder($payment)) {
            throw new LocalizedException(__('Unable to validate the order.'));
        }
        $this->sezzleHelper->logSezzleActions("Order validated at Sezzle");
        $this->sezzleHelper->logSezzleActions("Order UUID : $sezzleOrderUUID");
        $url = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_CAPTURE_LINK);
        $this->v2->captureByOrderUUID($url, $sezzleOrderUUID, $amountInCents, $amountInCents < $orderTotalInCents);
        if (!$payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID)) {
            $payment->setAdditionalInformation(
                self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID,
                $sezzleOrderUUID
            );
        }
        $capturedAmount = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_CAPTURE_AMOUNT);
        $capturedAmount += $amount;
        if (!$authAmount = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_AUTH_AMOUNT)) {
            $payment->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_AUTH_AMOUNT, $capturedAmount);
        }
        $payment->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_CAPTURE_AMOUNT, $capturedAmount);
        $payment->setTransactionId($reference)->setIsTransactionClosed(true);
        $this->sezzleHelper->logSezzleActions("Transaction ID : $reference");
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
        $this->sezzleHelper->logSezzleActions("****Release Started****");
        if (!$this->canVoid()) {
            throw new LocalizedException(__('The void action is not available.'));
        } elseif (!$this->validateOrder($payment)) {
            throw new LocalizedException(__('Unable to validate the order.'));
        } elseif (!$orderUUID = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID)) {
            throw new LocalizedException(__('Failed to void the payment.'));
        }
        $this->sezzleHelper->logSezzleActions("Order validated at Sezzle");
        $amountInCents = (int)(round($payment->getOrder()->getBaseGrandTotal() * 100, PayloadBuilder::PRECISION));

        $url = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_RELEASE_LINK);
        $this->v2->releasePaymentByOrderUUID($url, $orderUUID, $amountInCents);
        $payment->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_RELEASE_AMOUNT, $payment->getOrder()->getBaseGrandTotal());
        $payment->getOrder()->setState(Order::STATE_CLOSED)
                ->setStatus($payment->getOrder()->getConfig()->getStateDefaultStatus(Order::STATE_CLOSED));
        $this->sezzleHelper->logSezzleActions("Released payment successfully");
        $this->sezzleHelper->logSezzleActions("****Release end****");

        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this|Sezzle
     * @throws LocalizedException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        $this->sezzleHelper->logSezzleActions("****Refund Started****");
        if (!$this->canRefund()) {
            throw new LocalizedException(__('The refund action is not available.'));
        } elseif ($amount <= 0) {
            throw new LocalizedException(__('Invalid amount for refund.'));
        } elseif (!$this->validateOrder($payment)) {
            throw new LocalizedException(__('Unable to validate the order.'));
        }
        $this->sezzleHelper->logSezzleActions("Order validated at Sezzle");
        $amountInCents = (int)(round($amount * 100, PayloadBuilder::PRECISION));
        if ($sezzleOrderUUID = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORDER_UUID)) {
            $url = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_REFUND_LINK);
            $this->v2->refundByOrderUUID($url, $sezzleOrderUUID, $amountInCents);
            $refundedAmount = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_REFUND_AMOUNT);
            $refundedAmount += $amount;
            $payment->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_REFUND_AMOUNT, $refundedAmount);
        } else {
            throw new LocalizedException(__('Failed to refund the payment.'));
        }
        $this->sezzleHelper->logSezzleActions("Refunded payment successfully");
        $this->sezzleHelper->logSezzleActions("****Refund end****");

        return $this;
    }

    /**
     * Check whether payment method can be used
     *
     * @param CartInterface|null $quote
     * @return bool
     * @throws LocalizedException
     * @deprecated 100.2.0
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }

        $checkResult = new DataObject();
        $checkResult->setData('is_available', true);

        $merchantUUID = $this->sezzleApiConfig->getMerchantUUID();
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
            $url = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_GET_ORDER_LINK);
            $sezzleOrder = $this->v2->getOrder($url, $sezzleOrderUUID);
            if ($sezzleOrderUUID != $sezzleOrder->getUuid()) {
                $this->sezzleHelper->logSezzleActions("Order UUID not matching.");
                return false;
            } elseif (!$sezzleOrder->getAuthorization()) {
                $this->sezzleHelper->logSezzleActions("Order not authorized. Issue might be with limit.");
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
        $url = $order->getPayment()->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_GET_ORDER_LINK);
        $sezzleOrder = $this->v2->getOrder((string)$url, (string)$sezzleOrderUUID);
        if ($auth = $sezzleOrder->getAuthorization()) {
            $order->getPayment()->setAdditionalInformation(self::SEZZLE_AUTH_EXPIRY, $auth->getExpiration())->save();
        }
    }
}
