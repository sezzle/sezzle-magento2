<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
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
    const XML_PATH_PAYMENT_MODE = 'payment/sezzlepay/payment_mode';
    const XML_PATH_PRIVATE_KEY = 'payment/sezzlepay/private_key';
    const XML_PATH_MERCHANT_ID = 'payment/sezzlepay/merchant_id';

    private $liveCheckoutUrl = "https://gateway.sezzle.com";
    private $sandboxCheckoutUrl = "https://sandbox.gateway.sezzle.com";

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
    public function getPaymentMode()
    {
        return $this->getConfigValue(
            self::XML_PATH_PAYMENT_MODE,
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
}
