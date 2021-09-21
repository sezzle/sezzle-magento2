<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\System\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Config
 * @package Sezzle\Sezzlepay\Model\System
 */
class Config
{

    /**
     * @var int
     */
    private $storeId;
    /**
     * @var int
     */
    private $websiteId;
    /**
     * @var string
     */
    private $scope;
    /**
     * @var int
     */
    private $scopeId;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var Http
     */
    private $request;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var string
     */
    private $registerUrl = "https://dashboard.sezzle.com/merchant/signup";

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Http $request
     * @param StoreManagerInterface $storeManager
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Http $request,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->storeManager = $storeManager;

        // Find store ID and scope
        $this->websiteId = $request->getParam('website', 0);
        $this->storeId = $request->getParam('store', 0);
        $this->scope = $request->getParam('scope');

        // Website scope
        if ($this->websiteId) {
            $this->scope = !$this->scope ? 'websites' : $this->scope;
        } else {
            $this->websiteId = $storeManager->getWebsite()->getId();
        }

        // Store scope
        if ($this->storeId) {
            $this->websiteId = $this->storeManager->getStore($this->storeId)->getWebsite()->getId();
            $this->scope = !$this->scope ? 'stores' : $this->scope;
        } else {
            $this->storeId = $storeManager->getWebsite($this->websiteId)->getDefaultStore()->getId();
        }

        // Set scope ID
        switch ($this->scope) {
            case 'websites':
                $this->scopeId = $this->websiteId;
                break;
            case 'stores':
                $this->scopeId = $this->storeId;
                break;
            default:
                $this->scope = 'default';
                $this->scopeId = 0;
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
        return $this->scopeConfig->getValue($path, $this->scope, $this->scopeId);
    }

    /**
     * Return merchant country
     */
    public function getCountry()
    {
        $co = $this->getConfig('paypal/general/merchant_country');
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
}
