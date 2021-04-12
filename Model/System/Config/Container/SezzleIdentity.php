<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\System\Config\Container;

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
    public function getPublicKey($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::XML_PATH_PUBLIC_KEY,
            $this->getStore()->getStoreId(),
            $scope
        );
    }

    /**
     * @inheritdoc
     */
    public function getPrivateKey($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::XML_PATH_PRIVATE_KEY,
            $this->getStore()->getStoreId(),
            $scope
        );
    }

    /**
     * @inheritdoc
     */
    public function getPaymentMode($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::XML_PATH_PAYMENT_MODE,
            $this->getStore()->getStoreId(),
            $scope
        );
    }

    /**
     * @inheritdoc
     */
    public function getMerchantUUID($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::XML_PATH_MERCHANT_ID,
            $this->getStore()->getStoreId(),
            $scope
        );
    }

    /**
     * @inheritdoc
     */
    public function getGatewayRegion($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::XML_PATH_GATEWAY_REGION,
            $this->getStore()->getStoreId(),
            $scope
        );
    }

    /**
     * @inheritdoc
     */
    public function getSezzleBaseUrl($scope = ScopeInterface::SCOPE_STORE)
    {
        $gatewayRegion = $this->getGatewayRegion($scope) ?: $this->supportedRegions[0];
        return $this->getGatewayUrl('v2', $gatewayRegion, $scope);
    }

    /**
     * @inheritdoc
     */
    public function isLogTrackerEnabled($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::XML_PATH_LOG_TRACKER,
            $this->getStore()->getStoreId(),
            $scope
        );
    }

    /**
     * @inheritdoc
     */
    public function getPaymentAction($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::XML_PATH_PAYMENT_ACTION,
            $this->getStore()->getStoreId(),
            $scope
        );
    }

    /**
     * @inheritdoc
     */
    public function getMinCheckoutAmount($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::XML_PATH_MIN_CHECKOUT_AMOUNT,
            $this->getStore()->getStoreId(),
            $scope
        );
    }

    /**
     * @inheritdoc
     */
    public function isWidgetEnabledForPDP($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::XML_PATH_WIDGET_PDP,
            $this->getStore()->getStoreId(),
            $scope
        );
    }

    /**
     * @inheritdoc
     */
    public function isWidgetEnabledForCartPage($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::XML_PATH_WIDGET_CART,
            $this->getStore()->getStoreId(),
            $scope
        );
    }

    /**
     * @inheritdoc
     */
    public function isTokenizationAllowed($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::XML_PATH_TOKENIZE,
            $this->getStore()->getStoreId(),
            $scope
        ) ? true : false;
    }

    /**
     * @inheritdoc
     */
    public function isLogsSendingToSezzleAllowed($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::XML_PATH_CRON_LOGS,
            $this->getStore()->getStoreId(),
            $scope
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
    public function isSettlementReportsEnabled($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::XML_PATH_SETTLEMENT_REPORTS,
            $this->getStore()->getStoreId(),
            $scope
        ) ? true : false;
    }

    /**
     * @inheritdoc
     */
    public function getSettlementReportsRange($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::XML_PATH_SETTLEMENT_REPORTS_RANGE,
            $this->getStore()->getStoreId(),
            $scope
        );
    }

    /**
     * @inheritdoc
     */
    public function isInContextModeEnabled($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::XML_PATH_INCONTEXT_ACTIVE,
            $this->getStore()->getStoreId(),
            $scope
        );
    }

    /**
     * @inheritdoc
     */
    public function getInContextMode($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::XML_PATH_INCONTEXT_MODE,
            $this->getStore()->getStoreId(),
            $scope
        );
    }

    /**
     * @inheritdoc
     */
    public function isInContextCheckout($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->isInContextModeEnabled($scope);
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
    public function isInstallmentWidgetEnabled($scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfigValue(
            self::XML_PATH_WIDGET_INSTALLMENT,
            $this->getStore()->getStoreId(),
            $scope
        );
    }

    /**
     * @inheritDoc
     */
    public function getInstallmentWidgetPricePath($scope = ScopeInterface::SCOPE_STORE)
    {
        if (!$this->isInstallmentWidgetEnabled($scope)) {
            return "";
        }
        return $this->getConfigValue(
            self::XML_PATH_WIDGET_INSTALLMENT_PRICE,
            $this->getStore()->getStoreId(),
            $scope
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
    public function getGatewayUrl($apiVersion, $gatewayRegion = '', $scope = ScopeInterface::SCOPE_STORE)
    {
        $sezzleDomain = $this->getSezzleSomain($gatewayRegion);
        if ($this->getPaymentMode($scope) === self::SANDBOX_MODE) {
            return sprintf(self::GATEWAY_URL, 'staging.', $sezzleDomain, $apiVersion);
        }
        return sprintf(self::GATEWAY_URL, "", $sezzleDomain, $apiVersion);
    }
}
