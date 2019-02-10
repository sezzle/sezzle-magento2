<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 * @license     https://www.sezzle.com/LICENSE.txt
 */

namespace Sezzle\Sezzlepay\Model\Config\Container;


/**
 * Class SezzleApiIdentity
 * @package Sezzle\Sezzlepay\Model\Config\Container
 */
class SezzleApiIdentity extends Container implements SezzleApiConfigInterface
{
    const XML_PATH_PUBLIC_KEY = 'payment/sezzlepay/public_key';
    const XML_PATH_PAYMENT_ACTIVE = 'payment/sezzlepay/active';
    const XML_PATH_API_MODE = 'payment/sezzlepay/api_mode';
    const XML_PATH_BASE_URL = 'payment/sezzlepay/base_url';
    const XML_PATH_PRIVATE_KEY = 'payment/sezzlepay/private_key';
    const XML_PATH_MERCHANT_ID = 'payment/sezzlepay/merchant_id';

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
            $this->getStore()->getStoreId());
    }

    /**
     * @inheritdoc
     */
    public function getPrivateKey()
    {
        return $this->getConfigValue(
            self::XML_PATH_PRIVATE_KEY,
            $this->getStore()->getStoreId());
    }

    /**
     * @inheritdoc
     */
    public function getApiMode()
    {
        return $this->getConfigValue(
            self::XML_PATH_API_MODE,
            $this->getStore()->getStoreId());
    }

    /**
     * @inheritdoc
     */
    public function getMerchantId()
    {
        return $this->getConfigValue(
            self::XML_PATH_MERCHANT_ID,
            $this->getStore()->getStoreId());
    }

    /**
     * @inheritdoc
     */
    public function getSezzleBaseUrl()
    {
        return $this->getConfigValue(
            self::XML_PATH_BASE_URL,
            $this->getStore()->getStoreId());
    }
}