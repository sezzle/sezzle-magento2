<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\System\Config\Container;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Zend_Http_UserAgent_Mobile;

/**
 * Class SezzleIdentity
 * @package Sezzle\Sezzlepay\Model\System\Config\Container
 */
class SezzleIdentity extends Container implements SezzleConfigInterface
{
    const PROD_MODE = 'live';
    const SANDBOX_MODE = 'sandbox';

    const INCONTEXT_MODE_IFRAME = 'iframe';
    const INCONTEXT_MODE_POPUP = 'popup';

    const XML_PATH_PUBLIC_KEY = 'payment/sezzlepay/public_key';
    const XML_PATH_PAYMENT_ACTIVE = 'payment/sezzlepay/active';
    const XML_PATH_PAYMENT_MODE = 'payment/sezzlepay/payment_mode';
    const XML_PATH_PRIVATE_KEY = 'payment/sezzlepay/private_key';
    const XML_PATH_GATEWAY_REGION = 'payment/sezzlepay/gateway_region';
    const XML_PATH_MERCHANT_ID = 'payment/sezzlepay/merchant_id';
    const XML_PATH_PAYMENT_ACTION = 'payment/sezzlepay/payment_action';
    const XML_PATH_MIN_CHECKOUT_AMOUNT = 'payment/sezzlepay/min_checkout_amount';
    const XML_PATH_WIDGET_PDP = 'payment/sezzlepay/widget_pdp';
    const XML_PATH_WIDGET_CART = 'payment/sezzlepay/widget_cart';

    const XML_PATH_WIDGET_INSTALLMENT = 'payment/sezzlepay/widget_installment';
    const XML_PATH_WIDGET_INSTALLMENT_PRICE = 'payment/sezzlepay/widget_installment_price_path';

    const XML_PATH_TOKENIZE = 'payment/sezzlepay/tokenize';

    const XML_PATH_INCONTEXT_ACTIVE = 'payment/sezzlepay/active_in_context';
    const XML_PATH_INCONTEXT_MODE = 'payment/sezzlepay/in_context_mode';

    const XML_PATH_LOG_TRACKER = 'payment/sezzlepay/log_tracker';
    const XML_PATH_CRON_LOGS = 'payment/sezzlepay/send_logs_via_cron';

    private $liveGatewayUrl = "https://gateway.sezzle.com";
    private $sandboxGatewayUrl = "https://sandbox.gateway.sezzle.com";

    const XML_PATH_SETTLEMENT_REPORTS = 'payment/sezzlepay/settlement_reports';
    const XML_PATH_SETTLEMENT_REPORTS_RANGE = 'payment/sezzlepay/settlement_reports_range';

    const GATEWAY_URL = "https://%sgateway.%s/%s";
    const SEZZLE_DOMAIN = "%ssezzle.com";

    private $supportedRegions = ['US/CA', 'EU'];

    /**
     * @inheritdoc
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PAYMENT_ACTIVE,
            ScopeInterface::SCOPE_STORE,
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
    public function getGatewayRegion()
    {
        return $this->getConfigValue(
            self::XML_PATH_GATEWAY_REGION,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritdoc
     */
    public function getSezzleBaseUrl()
    {
        $gatewayRegion = $this->getGatewayRegion()
            ? $this->getGatewayRegion()
            : $this->supportedRegions[0];
        return $this->getGatewayUrl('v2', $gatewayRegion);
//        switch ($paymentMode) {
//            case self::PROD_MODE:
//                return $this->liveGatewayUrl;
//            case self::SANDBOX_MODE:
//                return $this->sandboxGatewayUrl;
//            default:
//                return null;
//        }
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

    /**
     * @inheritdoc
     */
    public function isSettlementReportsEnabled()
    {
        return $this->getConfigValue(
            self::XML_PATH_SETTLEMENT_REPORTS,
            $this->getStore()->getStoreId()
        ) ? true : false;
    }

    /**
     * @inheritdoc
     */
    public function getSettlementReportsRange()
    {
        return $this->getConfigValue(
            self::XML_PATH_SETTLEMENT_REPORTS_RANGE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritdoc
     */
    public function isInContextModeEnabled()
    {
        return $this->getConfigValue(
            self::XML_PATH_INCONTEXT_ACTIVE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritdoc
     */
    public function getInContextMode()
    {
        return $this->getConfigValue(
            self::XML_PATH_INCONTEXT_MODE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritdoc
     */
    public function isInContextCheckout()
    {
        return $this->isInContextModeEnabled();
    }

    /**
     * @inheritdoc
     */
    public function isMobileOrTablet()
    {
        $userAgent = $this->httpHeader->getHttpUserAgent();
        return Zend_Http_UserAgent_Mobile::match($userAgent, $_SERVER);
    }

    /**
     * @inheritDoc
     */
    public function isInstallmentWidgetEnabled()
    {
        return $this->getConfigValue(
            self::XML_PATH_WIDGET_INSTALLMENT,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritDoc
     */
    public function getInstallmentWidgetPricePath()
    {
        if (!$this->isInstallmentWidgetEnabled()) {
            return "";
        }
        return $this->getConfigValue(
            self::XML_PATH_WIDGET_INSTALLMENT_PRICE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * Get Sezzle domain
     *
     * @param string $gatewayRegion
     * @return string
     */
    private function getSezzleSomain($gatewayRegion = '')
    {
        switch ($gatewayRegion) {
            case $this->supportedRegions[1]:
                return sprintf(self::SEZZLE_DOMAIN, 'eu.');
            case $this->supportedRegions[0]:
            default:
                return sprintf(self::SEZZLE_DOMAIN, '');
        }
    }

    /**
     * @inheritDoc
     */
    public function getGatewayUrl($apiVersion, $gatewayRegion = '')
    {
        $sezzleDomain = $this->getSezzleSomain($gatewayRegion);
        if ($this->getPaymentMode() === self::SANDBOX_MODE) {
            return sprintf(self::GATEWAY_URL, 'sandbox.', $sezzleDomain, $apiVersion);
        }
        return sprintf(self::GATEWAY_URL, "", $sezzleDomain, $apiVersion);
    }
}
