<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\System\Config\Container;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface IdentityInterface
 * @package Sezzle\Sezzlepay\Model\System\Config\Container
 */
interface SezzleConfigInterface extends IdentityInterface
{

    /**
     * Get public key
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getPublicKey();

    /**
     * Get private key
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getPrivateKey();

    /**
     * Get Payment mode
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getPaymentMode();

    /**
     * Get Merchant UUID
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getMerchantUUID();

    /**
     * Get Sezzle base url
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getSezzleBaseUrl();

    /**
     * Get log tracker status
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isLogTrackerEnabled();

    /**
     * Get payment action
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getPaymentAction();

    /**
     * Get min checkout amount
     * @return string|null
     * @throws NoSuchEntityException
     */
    public function getMinCheckoutAmount();

    /**
     * Static Widget Status
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isStaticWidgetEnabled();

    /**
     * Get widget script status for PDP
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isWidgetEnabledForPDP();

    /**
     * Get widget script status for cart page
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isWidgetEnabledForCartPage();

    /**
     * Get tokenization status
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isTokenizationAllowed();

    /**
     * Get complete url
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isLogsSendingToSezzleAllowed();

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
     * @return bool
     */
    public function isSettlementReportsEnabled();

    /**
     * Get Settlement Reports range
     * @return int
     */
    public function getSettlementReportsRange();

    /**
     * Get InContext Checkout Mode
     * @return string
     */
    public function getInContextMode();
}
