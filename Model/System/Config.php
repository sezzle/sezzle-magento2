<?php

namespace Sezzle\Sezzlepay\Model\System;

/**
 * Class Config
 * @package Sezzle\Sezzlepay\Model\System
 */
class Config
{

    /**
     * @var int
     */
    private $_storeId;
    /**
     * @var int
     */
    private $_websiteId;
    /**
     * @var string
     */
    private $_scope;
    /**
     * @var int
     */
    private $_scopeId;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    private $request;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var string
     */
    private $registerUrl = "https://dashboard.sezzle.com/merchant/signup";

    /**
     * @var string[]
     */
    private $supportedCountryCodes = [
        'US',
        'CA'
    ];

    /**
     * Config constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->storeManager = $storeManager;

        // Find store ID and scope
        $this->_websiteId = $request->getParam('website', 0);
        $this->_storeId = $request->getParam('store', 0);
        $this->_scope = $request->getParam('scope');

        // Website scope
        if ($this->_websiteId) {
            $this->_scope = !$this->_scope ? 'websites' : $this->_scope;
        } else {
            $this->_websiteId = $storeManager->getWebsite()->getId();
        }

        // Store scope
        if ($this->_storeId) {
            $this->_websiteId = $this->storeManager->getStore($this->_storeId)->getWebsite()->getId();
            $this->_scope = !$this->_scope ? 'stores' : $this->_scope;
        } else {
            $this->_storeId = $storeManager->getWebsite($this->_websiteId)->getDefaultStore()->getId();
        }

        // Set scope ID
        switch ($this->_scope) {
            case 'websites':
                $this->_scopeId = $this->_websiteId;
                break;
            case 'stores':
                $this->_scopeId = $this->_storeId;
                break;
            default:
                $this->_scope = 'default';
                $this->_scopeId = 0;
                break;
        }
    }

    /**
     * Return register endpoint URL
     */
    public function getSezzleRegisterUrl()
    {
        return $this->registerUrl;
    }

    /**
     * Return config value based on scope and scope ID
     */
    public function getConfig($path)
    {
        return $this->scopeConfig->getValue($path, $this->_scope, $this->_scopeId);
    }

    /**
     * Return merchant country
     */
    public function getCountry()
    {
        $co = $this->getConfig('payment/sezzlepay/merchant_country');
        return $co ? $co : 'US';
    }

    /**
     * Return array of config for JSON Sezzle variable.
     */
    public function getSezzleJsonConfig()
    {
        return [
            'co' => $this->getCountry(),
            'sezzleUrl' => $this->getSezzleRegisterUrl()
        ];
    }

    /**
     * Return array of supported merchant country codes.
     */
    public function getSupportedMerchantCountryCodes()
    {
        return $this->supportedCountryCodes;
    }
}
