<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\System\Config\Container;

/**
 * Class SezzleIdentity
 * @package Sezzle\Sezzlepay\Model\System\Config\Container
 */
class SezzleIdentity extends Container implements SezzleConfigInterface
{
    const PROD_MODE = 'live';
    const SANDBOX_MODE = 'sandbox';

    const XML_PATH_PUBLIC_KEY = 'payment/sezzlepay/public_key';
    const XML_PATH_PAYMENT_ACTIVE = 'payment/sezzlepay/active';
    const XML_PATH_PAYMENT_MODE = 'payment/sezzlepay/payment_mode';
    const XML_PATH_PRIVATE_KEY = 'payment/sezzlepay/private_key';
    const XML_PATH_MERCHANT_ID = 'payment/sezzlepay/merchant_id';
    const XML_PATH_PAYMENT_ACTION = 'payment/sezzlepay/payment_action';
    const XML_PATH_MIN_CHECKOUT_AMOUNT = 'payment/sezzlepay/min_checkout_amount';
    const XML_PATH_STATIC_WIDGET = 'payment/sezzlepay/static_widget';
    const XML_PATH_WIDGET_PDP = 'payment/sezzlepay/widget_pdp';
    const XML_PATH_WIDGET_CART = 'payment/sezzlepay/widget_cart';
    const XML_PATH_TOKENIZE = 'payment/sezzlepay/tokenize';

    const XML_PATH_LOG_TRACKER = 'payment/sezzlepay/log_tracker';
    const XML_PATH_CRON_LOGS = 'payment/sezzlepay/send_logs_via_cron';

    private $liveGatewayUrl = "https://gateway.sezzle.com";
    private $sandboxGatewayUrl = "https://sandbox.gateway.sezzle.com";

    /**
     * @inheritdoc
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PAYMENT_ACTIVE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritdoc
     */
    public function getPublicKey()
    {
        return $this->getConfigValue(
            self::XML_PATH_PUBLIC_KEY,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritdoc
     */
    public function getPrivateKey()
    {
        return $this->getConfigValue(
            self::XML_PATH_PRIVATE_KEY,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritdoc
     */
    public function getPaymentMode()
    {
        return $this->getConfigValue(
            self::XML_PATH_PAYMENT_MODE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritdoc
     */
    public function getMerchantUUID()
    {
        return $this->getConfigValue(
            self::XML_PATH_MERCHANT_ID,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritdoc
     */
    public function getSezzleBaseUrl()
    {
        $paymentMode = $this->getPaymentMode();
        switch ($paymentMode) {
            case self::PROD_MODE:
                return $this->liveGatewayUrl;
            case self::SANDBOX_MODE:
                return $this->sandboxGatewayUrl;
            default:
                return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function isLogTrackerEnabled()
    {
        return $this->getConfigValue(
            self::XML_PATH_LOG_TRACKER,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritdoc
     */
    public function getPaymentAction()
    {
        return $this->getConfigValue(
            self::XML_PATH_PAYMENT_ACTION,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritdoc
     */
    public function getMinCheckoutAmount()
    {
        return $this->getConfigValue(
            self::XML_PATH_MIN_CHECKOUT_AMOUNT,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritdoc
     */
    public function isStaticWidgetEnabled()
    {
        return $this->getConfigValue(
            self::XML_PATH_STATIC_WIDGET,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritdoc
     */
    public function isWidgetEnabledForPDP()
    {
        return $this->getConfigValue(
            self::XML_PATH_WIDGET_PDP,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritdoc
     */
    public function isWidgetEnabledForCartPage()
    {
        return $this->getConfigValue(
            self::XML_PATH_WIDGET_CART,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritdoc
     */
    public function isTokenizationAllowed()
    {
        return $this->getConfigValue(
            self::XML_PATH_TOKENIZE,
            $this->getStore()->getStoreId()
        ) ? true : false;
    }

    /**
     * @inheritdoc
     */
    public function isLogsSendingToSezzleAllowed()
    {
        return $this->getConfigValue(
            self::XML_PATH_CRON_LOGS,
            $this->getStore()->getStoreId()
        ) ? true : false;
    }

    /**
     * @inheritdoc
     */
    public function getCompleteUrl()
    {
        return $this->urlBuilder
            ->getUrl(
                "sezzle/payment/complete/",
                ['_secure' => true]
            );
    }

    /**
     * @inheritdoc
     */
    public function getCancelUrl()
    {
        return $this->urlBuilder->getUrl("sezzle/payment/cancel/", ['_secure' => true]);
    }

    /**
     * @inheritdoc
     */
    public function getTokenizePaymentCompleteURL()
    {
        return $this->urlBuilder->getUrl(
            "sezzle/tokenize/paymentComplete",
            [
                '_secure' => true
            ]
        );
    }
}
