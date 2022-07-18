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
    const ADDITIONAL_INFORMATION_KEY_RELEASE_AMOUNT = 'sezzle_release_amount';

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
}
