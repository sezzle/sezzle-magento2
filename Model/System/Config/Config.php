<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\System\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface as StoreScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleIdentity;
use Sezzle\Sezzlepay\Model\System\Config\Source\Payment\GatewayRegion;

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
     * @var string[]
     */
    private $supportedCountryCodes = [
        'US',
        'CA'
    ];
    /**
     * @var GatewayRegion
     */
    private $gatewayRegion;
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    private $resourceConfig;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Http $request
     * @param StoreManagerInterface $storeManager
     * @param GatewayRegion $gatewayRegion
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Http $request,
        StoreManagerInterface $storeManager,
        GatewayRegion $gatewayRegion,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->gatewayRegion = $gatewayRegion;
        $this->resourceConfig = $resourceConfig;

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
     * @return array
     */
    public function getSupportedMerchantCountryCodes()
    {
        return $this->supportedCountryCodes;
    }

    /**
     * Set gateway region
     *
     * @param int $websiteScope
     * @param int $storeScope
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setGatewayRegion($websiteScope, $storeScope)
    {
        $scope = 'default';
        $scopeId = 0;
        if ($websiteScope) {
            $scope = StoreScopeInterface::SCOPE_WEBSITES;
            $scopeId = $websiteScope;
        } elseif ($storeScope) {
            $scope = StoreScopeInterface::SCOPE_STORES;
            $scopeId = $storeScope;
        }

        $gatewayRegion = $this->gatewayRegion->getValue();
        if (!$gatewayRegion) {
            throw new AuthenticationException(__('Unable to authenticate.'));
        }

        $this->resourceConfig->saveConfig(
            SezzleIdentity::XML_PATH_GATEWAY_REGION,
            $gatewayRegion,
            $scope,
            $scopeId
        );
    }
}
