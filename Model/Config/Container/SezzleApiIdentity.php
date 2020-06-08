<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model\Config\Container;

/**
 * Class SezzleApiIdentity
 * @package Sezzle\Payment\Model\Config\Container
 */
class SezzleApiIdentity extends Container implements SezzleApiConfigInterface
{
    const XML_PATH_PUBLIC_KEY = 'payment/sezzle/public_key';
    const XML_PATH_PAYMENT_ACTIVE = 'payment/sezzle/active';
    const XML_PATH_PAYMENT_MODE = 'payment/sezzle/payment_mode';
    const XML_PATH_PRIVATE_KEY = 'payment/sezzle/private_key';
    const XML_PATH_MERCHANT_ID = 'payment/sezzle/merchant_id';
    const XML_PATH_LOG_TRACKER = 'payment/sezzle/log_tracker';
    const XML_PATH_PAYMENT_ACTION = 'payment/sezzle/payment_action';
    const XML_PATH_MIN_CHECKOUT_AMOUNT = 'payment/sezzle/min_checkout_amount';
    const XML_PATH_WIDGET_PDP = 'payment/sezzle/widget_pdp';
    const XML_PATH_WIDGET_CART = 'payment/sezzle/widget_cart';
    const XML_PATH_TOKENIZE = 'payment/sezzle/tokenize';

    private $liveCheckoutUrl = "https://gateway.sezzle.com";
    private $sandboxCheckoutUrl = "https://staging.gateway.sezzle.com";

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
    public function getMerchantId()
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
            case 'live':
                return $this->liveCheckoutUrl;
                break;
            case 'sandbox':
                return $this->sandboxCheckoutUrl;
                break;
            default:
                break;
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
    public function isWidgetScriptAllowedForPDP()
    {
        return $this->getConfigValue(
            self::XML_PATH_WIDGET_PDP,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritdoc
     */
    public function isWidgetScriptAllowedForCartPage()
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
    public function getCompleteUrl($orderId, $reference)
    {
        return $this->urlBuilder
            ->getUrl(
                "sezzle/payment/complete/id/$orderId/magento_sezzle_id/$reference",
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
