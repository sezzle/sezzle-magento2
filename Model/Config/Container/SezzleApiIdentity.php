<?php
/**
 * Created by PhpStorm.
 * User: arijit
 * Date: 1/27/2019
 * Time: 4:37 PM
 */

namespace Sezzle\Sezzlepay\Model\Config\Container;


class SezzleApiIdentity extends Container implements SezzleApiConfigInterface
{
    const XML_PATH_PUBLIC_KEY = 'payment/sezzlepay/public_key';
    const XML_PATH_PAYMENT_ACTIVE = 'payment/sezzlepay/active';
    const XML_PATH_API_MODE = 'payment/sezzlepay/api_mode';
    const XML_PATH_BASE_URL = 'payment/sezzlepay/base_url';
    const XML_PATH_PRIVATE_KEY = 'payment/sezzlepay/private_key';

    /**
     * @return bool
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
     * @return mixed
     */
    public function getPublicKey()
    {
        return $this->getConfigValue(
            self::XML_PATH_PUBLIC_KEY,
            $this->getStore()->getStoreId());
    }

    public function getPrivateKey()
    {
        return $this->getConfigValue(
            self::XML_PATH_PRIVATE_KEY,
            $this->getStore()->getStoreId());
    }

    public function getApiMode()
    {
        return $this->getConfigValue(
            self::XML_PATH_API_MODE,
            $this->getStore()->getStoreId());
    }

    /**
     * @return mixed
     */
    public function getMerchantId()
    {
        return $this->getConfigValue(
            self::XML_PATH_MERCHANT_ID,
            $this->getStore()->getStoreId());
    }

    public function getSezzleBaseUrl()
    {
        return $this->getConfigValue(
            self::XML_PATH_BASE_URL,
            $this->getStore()->getStoreId());
    }
}