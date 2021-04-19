<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\System\Config\Container;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

/**
 * Interface IdentityInterface
 * @package Sezzle\Sezzlepay\Model\System\Config\Container
 */
interface SezzleConfigInterface extends IdentityInterface
{

    /**
     * Get public key
     * @param string $scope
     * @return string|null
     */
    public function getPublicKey($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Get private key
     * @param string $scope
     * @return string|null
     */
    public function getPrivateKey($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Get Payment mode
     * @param string $scope
     * @return string|null
     */
    public function getPaymentMode($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Get Merchant UUID
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getMerchantUUID($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Get Sezzle base url
     * @param string $scope
     * @return string|null
     */
    public function getSezzleBaseUrl($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Get log tracker status
     * @param string $scope
     * @return bool
     */
    public function isLogTrackerEnabled($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Get payment action
     * @param string $scope
     * @return string|null
     */
    public function getPaymentAction($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Get min checkout amount
     * @param string $scope
     * @return string|null
     */
    public function getMinCheckoutAmount($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Get widget script status for PDP
     * @param string $scope
     * @return bool
     */
    public function isWidgetEnabledForPDP($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Get widget script status for cart page
     * @param string $scope
     * @return bool
     */
    public function isWidgetEnabledForCartPage($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Get installment widget status for checkout page
     * @param string $scope
     * @return bool
     */
    public function isInstallmentWidgetEnabled($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Get installment widget price path
     * @param string $scope
     * @return string
     */
    public function getInstallmentWidgetPricePath($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Get tokenization status
     * @param string $scope
     * @return bool
     */
    public function isTokenizationAllowed($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Get complete url
     * @param string $scope
     * @return bool
     */
    public function isLogsSendingToSezzleAllowed($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Get complete url
     * @return string
     */
    public function getCompleteUrl();

    /**
     * Get cancel url
     * @return string
     */
    public function getCancelUrl();

    /**
     * Get tokenize payment complete url
     * @return string
     */
    public function getTokenizePaymentCompleteURL();

    /**
     * Status of Settlement Reports
     * @param string $scope
     * @return bool
     */
    public function isSettlementReportsEnabled($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Get Settlement Reports range
     * @param string $scope
     * @return int
     */
    public function getSettlementReportsRange($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Check if InContext Solution is active
     * @param string $scope
     * @return bool
     */
    public function isInContextModeEnabled($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Get InContext Checkout Mode
     * @param string $scope
     * @return string
     */
    public function getInContextMode($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Check if current checkout is in context
     *
     * @param string $scope
     * @return bool
     */
    public function isInContextCheckout($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Check if Device is Mobile or Tablet
     *
     * @return bool
     */
    public function isMobileOrTablet();

    /**
     * Get Gateway URL
     *
     * @param string $apiVersion
     * @param string $gatewayRegion
     * @param string $scope
     * @return mixed
     */
    public function getGatewayUrl($apiVersion, $gatewayRegion = '', $scope = ScopeInterface::SCOPE_STORE);

    /**
     * Get Widget URL
     *
     * @param string $apiVersion
     * @param string $gatewayRegion
     * @return mixed
     */
    public function getWidgetUrl($apiVersion, $gatewayRegion = '');

    /**
     * Get Gateway Region
     *
     * @param string $scope
     * @return string|null
     */
    public function getGatewayRegion($scope = ScopeInterface::SCOPE_STORE);

    /**
     * Set Gateway Region
     *
     * @param int $websiteScope
     * @param int $storeScope
     * @return mixed
     */
    public function setGatewayRegion($websiteScope, $storeScope);

    /**
     * Get logo by gateway region
     *
     * @return mixed
     */
    public function getLogo();
}
