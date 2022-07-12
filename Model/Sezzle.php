<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model;

use Exception;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Sezzle\Sezzlepay\Api\V1Interface;
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Helper\Util;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleIdentity;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResourceModel;

/**
 * Class Sezzle
 * @package Sezzle\Sezzlepay\Model
 */
class Sezzle
{
    const PAYMENT_CODE = 'sezzlepay';
    const ADDITIONAL_INFORMATION_KEY_REFERENCE_ID = 'sezzle_reference_id';
    const ADDITIONAL_INFORMATION_KEY_ORIGINAL_ORDER_UUID = 'sezzle_original_order_uuid';
    const ADDITIONAL_INFORMATION_KEY_EXTENDED_ORDER_UUID = 'sezzle_extended_order_uuid';
    const SEZZLE_AUTH_EXPIRY = 'sezzle_auth_expiry';
    const SEZZLE_CAPTURE_EXPIRY = 'sezzle_capture_expiry';
    const SEZZLE_ORDER_TYPE = 'sezzle_order_type';

    const ADDITIONAL_INFORMATION_KEY_REFERENCE_ID_V1 = 'sezzle_order_id';

    const ADDITIONAL_INFORMATION_KEY_AUTH_AMOUNT = 'sezzle_auth_amount';
    const ADDITIONAL_INFORMATION_KEY_CAPTURE_AMOUNT = 'sezzle_capture_amount';
    const ADDITIONAL_INFORMATION_KEY_REFUND_AMOUNT = 'sezzle_refund_amount';
    const ADDITIONAL_INFORMATION_KEY_RELEASE_AMOUNT = 'sezzle_order_amount';

    const ADDITIONAL_INFORMATION_KEY_GET_ORDER_LINK = 'sezzle_get_order_link';
    const ADDITIONAL_INFORMATION_KEY_CAPTURE_LINK = 'sezzle_capture_link';
    const ADDITIONAL_INFORMATION_KEY_REFUND_LINK = 'sezzle_refund_link';
    const ADDITIONAL_INFORMATION_KEY_RELEASE_LINK = 'sezzle_release_link';
    const ADDITIONAL_INFORMATION_KEY_CREATE_ORDER_LINK = 'sezzle_create_order_link';
    const ADDITIONAL_INFORMATION_KEY_GET_CUSTOMER_LINK = 'sezzle_get_customer_link';
    const ADDITIONAL_INFORMATION_KEY_GET_TOKEN_DETAILS_LINK = 'sezzle_token_link';

    /**
     * @var string
     */
    protected $_code = self::PAYMENT_CODE;
    /**
     * @var bool
     */
    protected $_canOrder = true;
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
    protected $_canVoid = true;
    /**
     * @var bool
     */
    protected $_canUseInternal = false;

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
     * @var System\Config\Container\SezzleConfigInterface
     */
    private $sezzleConfig;
    /**
     * @var Tokenize
     */
    private $tokenizeModel;
    /**
     * @var V1Interface
     */
    private $v1;
    /**
     * @var DateTime
     */
    private $dateTime;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var QuoteResourceModel
     */
    private $quoteResourceModel;

    /**
     * Sezzle constructor.
     * @param System\Config\Container\SezzleConfigInterface $sezzleConfig
     * @param Data $sezzleHelper
     * @param QuoteRepository $quoteRepository
     * @param V2Interface $v2
     * @param CustomerSession $customerSession
     * @param Tokenize $tokenizeModel
     * @param V1Interface $v1
     * @param DateTime $dateTime
     * @param CheckoutSession $checkoutSession
     * @param QuoteResourceModel $quoteResourceModel
     */
    public function __construct(
        System\Config\Container\SezzleConfigInterface $sezzleConfig,
        Data                                          $sezzleHelper,
        QuoteRepository                               $quoteRepository,
        V2Interface                                   $v2,
        CustomerSession                               $customerSession,
        Tokenize                                      $tokenizeModel,
        V1Interface                                   $v1,
        DateTime                                      $dateTime,
        CheckoutSession                               $checkoutSession,
        QuoteResourceModel                            $quoteResourceModel
    )
    {
        $this->sezzleHelper = $sezzleHelper;
        $this->sezzleConfig = $sezzleConfig;
        $this->quoteRepository = $quoteRepository;
        $this->v2 = $v2;
        $this->customerSession = $customerSession;
        $this->tokenizeModel = $tokenizeModel;
        $this->v1 = $v1;
        $this->dateTime = $dateTime;
        $this->checkoutSession = $checkoutSession;
        $this->quoteResourceModel = $quoteResourceModel;
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
        $payment = $quote->getPayment();
        $referenceID = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID);
        $this->sezzleHelper->logSezzleActions("Reference Id : $referenceID");
        $this->sezzleHelper->logSezzleActions("Payment Type : " . 'authorize');
        $additionalInformation[self::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID] = $referenceID;
        $redirectURL = '';
        if ($quote->getCustomer()
            && $this->tokenizeModel->isCustomerUUIDValid($quote)) {
            $this->sezzleHelper->logSezzleActions("Tokenized Checkout");
            $tokenizeInformation = [
                Tokenize::ATTR_SEZZLE_CUSTOMER_UUID => $quote->getCustomer()->getCustomAttribute(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID)->getValue(),
                Tokenize::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION => $quote->getCustomer()->getCustomAttribute(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION)->getValue(),
                self::ADDITIONAL_INFORMATION_KEY_CREATE_ORDER_LINK => $quote->getCustomer()->getCustomAttribute(self::ADDITIONAL_INFORMATION_KEY_CREATE_ORDER_LINK)->getValue(),
            ];
            $additionalInformation = array_merge($additionalInformation, $tokenizeInformation);
            $redirectURL = $this->sezzleConfig->getTokenizePaymentCompleteURL();
        } else {
            $this->sezzleHelper->logSezzleActions("Typical Checkout");
            $session = $this->v2->createSession($referenceID, $quote->getStoreId());
            if ($session->getOrder()) {
                $redirectURL = $session->getOrder()->getCheckoutUrl();
                if ($session->getOrder()->getUuid()) {
                    $orderUUID = [
                        self::ADDITIONAL_INFORMATION_KEY_ORIGINAL_ORDER_UUID => $session->getOrder()->getUuid()
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
            if ($tokenizeObject = $session->getTokenize()) {
                $this->customerSession->setCustomerSezzleToken($tokenizeObject->getToken());
                $this->customerSession->setCustomerSezzleTokenExpiration($tokenizeObject->getExpiration());
                $this->customerSession->setCustomerSezzleTokenStatus(true);

                if (is_array($tokenizeObject->getLinks())) {
                    foreach ($tokenizeObject->getLinks() as $link) {
                        if ($link->getRel() == self::ADDITIONAL_INFORMATION_KEY_GET_TOKEN_DETAILS_LINK) {
                            $this->customerSession->setGetTokenDetailsLink($link->getHref());
                        }
                    }
                }
            }
        }
        if (!$redirectURL) {
            $this->sezzleHelper->logSezzleActions("Redirect URL was not received from Sezzle.");
            throw new LocalizedException(__('Unable to start your checkout with Sezzle.'));
        }
        $payment->setAdditionalInformation(array_merge(
            $additionalInformation,
            [self::SEZZLE_ORDER_TYPE => SezzleIdentity::API_VERSION_V2]
        ));
        $this->quoteResourceModel->save($quote->collectTotals());
        $this->checkoutSession->replaceQuote($quote);
        $this->sezzleHelper->logSezzleActions("Checkout URL : $redirectURL");
        return $redirectURL;
    }

    /**
     * Track Cart Item Information
     *
     * @param InfoInterface $payment
     */
    private function trackCartItemInformation(InfoInterface $payment)
    {
        try {
            if ($quoteId = $payment->getOrder()->getQuoteId()) {
                $quote = $this->quoteRepository->get($quoteId);
                $this->sezzleHelper->logSezzleActions("Collecting Quote Item Information");
                foreach ($quote->getAllVisibleItems() as $item) {
                    $this->sezzleHelper->logSezzleActions(
                        "Sku : " . $item->getSku() .
                        " | " . "Qty : " . $item->getQty() .
                        " | " . "Price : " . $item->getPrice()
                    );
                }
                $this->sezzleHelper->logSezzleActions("Collection done");
            }
        } catch (Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
        }
    }

    /**
     * Check partial capture availability
     *
     * @return bool
     *
     */
    public function canCapturePartial()
    {
        return $this->sezzleConfig->getGatewayRegion() === 'IN' ? false : $this->_canCapturePartial;
    }

    /**
     * Check void availability.
     *
     * @return bool
     *
     */
    public function canVoid()
    {
        return $this->sezzleConfig->getGatewayRegion() === 'IN' ? false : $this->_canVoid;
    }

    /**
     * Check whether payment method can be used
     *
     * @param CartInterface|null $quote
     * @return bool
     * @throws LocalizedException
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if (!$this->isActive($quote ? $quote->getStoreId() : null)) {
            return false;
        }

        $checkResult = new DataObject();
        $checkResult->setData('is_available', true);

        $merchantUUID = $this->sezzleConfig->getMerchantUUID();
        $publicKey = $this->sezzleConfig->getPublicKey();
        $privateKey = $this->sezzleConfig->getPrivateKey();
        $minCheckoutAmount = $this->sezzleConfig->getMinCheckoutAmount();

        if (($this->getCode() == self::PAYMENT_CODE)
            && ((!$merchantUUID || !$publicKey || !$privateKey)
                || ($quote && ($quote->getBaseGrandTotal() < $minCheckoutAmount)))) {
            $checkResult->setData('is_available', false);
        }

        return $checkResult->getData('is_available');
    }

    /**
     * Validate Order
     *
     * @param InfoInterface $payment
     * @param bool $isAuthValid
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function validateOrder($payment, $isAuthValid = true)
    {
        if ($orderReferenceID = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID_V1)) {
            $sezzleOrder = $this->v1->getOrder(
                $orderReferenceID,
                $payment->getOrder()->getStoreId()
            );
            if (!$sezzleOrder->getCaptureExpiration()) {
                return false;
            }
            return true;
        }

        switch ($isAuthValid) {
            case true:
                $sezzleOrderUUID = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORIGINAL_ORDER_UUID);
                break;
            case false:
                $sezzleOrderUUID = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_EXTENDED_ORDER_UUID);
                break;
            default:
                $this->sezzleHelper->logSezzleActions("Unable to determine auth expiration validity.");
                return false;
        }
        if ($sezzleOrderUUID) {
            $sezzleOrder = $this->v2->getOrder("", $sezzleOrderUUID, $payment->getOrder()->getStoreId());
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
        $sezzleOrderUUID = $order->getPayment()->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORIGINAL_ORDER_UUID);
        $url = $order->getPayment()->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_GET_ORDER_LINK);
        $sezzleOrder = $this->v2->getOrder((string)$url, (string)$sezzleOrderUUID, $order->getStoreId());
        if ($auth = $sezzleOrder->getAuthorization()) {
            $order->getPayment()->setAdditionalInformation(self::SEZZLE_AUTH_EXPIRY, $auth->getExpiration())->save();
        }
    }

    /**
     * Check if invoice can be created or not
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function canInvoice($order)
    {
        $paymentType = $order->getPayment()->getAdditionalInformation('payment_type');
        if ($order->getPayment()->getMethod() == Sezzle::PAYMENT_CODE
            && $paymentType === 'authorize') {
            $sezzleOrderType = $order->getPayment()->getAdditionalInformation(self::SEZZLE_ORDER_TYPE);
            $currentTimestamp = $this->dateTime->timestamp('now');
            if ($sezzleOrderType == SezzleIdentity::API_VERSION_V2) {
                $authExpiry = $order->getPayment()->getAdditionalInformation(self::SEZZLE_AUTH_EXPIRY);
                $expirationTimestamp = $this->dateTime->timestamp($authExpiry);
            } else {
                $captureExpiry = $order->getPayment()->getAdditionalInformation(self::SEZZLE_CAPTURE_EXPIRY);
                $expirationTimestamp = $this->dateTime->timestamp($captureExpiry);
            }
            if ($expirationTimestamp < $currentTimestamp) {
                $this->sezzleHelper->logSezzleActions("Authorization expired.");
                return false;
            }
        }
        $this->sezzleHelper->logSezzleActions("Authorization valid.");
        return true;
    }

    /**
     * Handling of V1 Capture
     *
     * @param InfoInterface $payment
     * @param int $amount
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function handleV1Capture($payment, $amount)
    {
        $orderReferenceID = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID_V1);
        $amountInCents = Util::formatToCents($amount);
        if (!$orderReferenceID) {
            throw new LocalizedException(__("Unable to capture. Order Reference ID is missing."));
        }
        $sezzleOrder = $this->v1->getOrder(
            $orderReferenceID,
            $payment->getOrder()->getStoreId()
        );
        if ($amountInCents != $sezzleOrder->getAmountInCents()) {
            throw new LocalizedException(__('Unable to capture due to invalid order total.'));
        } elseif ($sezzleOrder->getCaptureExpiration() == null) {
            throw new LocalizedException(__('Unable to capture as the order is not authorized.'));
        }
        $isCaptured = $this->v1->capture(
            $orderReferenceID,
            $payment->getOrder()->getStoreId()
        );
        if (!$isCaptured) {
            throw new LocalizedException(__('Unable to capture the amount.'));
        }
        return $orderReferenceID;
    }

    /**
     * Handling of V2 Capture
     *
     * @param string $sezzleOrderUUID
     * @param InfoInterface $payment
     * @param int $amount
     * @return string
     */
    private function handleV2Capture($sezzleOrderUUID, $payment, $amount)
    {
        $this->sezzleHelper->logSezzleActions($sezzleOrderUUID);
        $amountInCents = Util::formatToCents($amount);
        $isPartialCapture = $payment->formatAmount($payment->getOrder()->getBaseGrandTotal(), true)
            != $payment->formatAmount($amount, true);
        $captureTxnUUID = $this->v2->capture(
            "",
            $sezzleOrderUUID,
            $amountInCents,
            $isPartialCapture,
            $payment->getOrder()->getBaseCurrencyCode(),
            $payment->getOrder()->getStoreId()
        );
        if (!$payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_ORIGINAL_ORDER_UUID)) {
            $payment->setAdditionalInformation(
                self::ADDITIONAL_INFORMATION_KEY_ORIGINAL_ORDER_UUID,
                $sezzleOrderUUID
            );
        }
        $capturedAmount = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_CAPTURE_AMOUNT);
        $capturedAmount += $amount;
        if (!$authAmount = $payment->getAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_AUTH_AMOUNT)) {
            $payment->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_AUTH_AMOUNT, $capturedAmount);
        }
        $payment->setAdditionalInformation(self::ADDITIONAL_INFORMATION_KEY_CAPTURE_AMOUNT, $capturedAmount);
        $payment->setAdditionalInformation($captureTxnUUID, $sezzleOrderUUID);
        return $captureTxnUUID;
    }
}
