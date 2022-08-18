<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Adminhtml\System\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Paypal\Block\Adminhtml\System\Config\Field\Country;
use Magento\Store\Model\StoreManagerInterface;

/**
 * SezzleRegisterAdmin
 */
class SezzleRegisterAdmin extends Template
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Data
     */
    private $jsonHelper;

    /**
     * @var \Sezzle\Sezzlepay\Helper\Data
     */
    private $sezzleHelper;

    /**
     * @var string
     */
    const MERCHANT_DASHBOARD_URL = "https://dashboard.sezzle.com/merchant";

    /**
     * @param Context $context
     * @param Data $jsonHelper
     * @param \Sezzle\Sezzlepay\Helper\Data $sezzleHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Http $request
     * @param StoreManagerInterface $storeManager
     * @param array $data
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function __construct(
        Context                       $context,
        Data                          $jsonHelper,
        \Sezzle\Sezzlepay\Helper\Data $sezzleHelper,
        ScopeConfigInterface          $scopeConfig,
        Http                          $request,
        StoreManagerInterface         $storeManager,
        array                         $data = []
    )
    {
        parent::__construct($context, $data);
        $this->jsonHelper = $jsonHelper;
        $this->sezzleHelper = $sezzleHelper;
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
     * Return config value based on scope and scope ID
     */
    public function getConfig($path): ?string
    {
        return $this->scopeConfig->getValue($path, $this->scope, $this->scopeId);
    }

    /**
     * Return merchant country
     */
    public function getCountry(): string
    {
        $co = $this->getConfig(Country::FIELD_CONFIG_PATH);
        return $co ?: 'US';
    }

    /**
     * Return config settings
     */
    public function getJsonConfig(): string
    {
        return $this->jsonHelper->jsonEncode([
            'co' => $this->getCountry(),
            'merchant_signup_url' => self::MERCHANT_DASHBOARD_URL . '/signup'
        ]);
    }

    /**
     * Get Sezzle Module Version
     * @return string
     */
    public function getSezzleModuleVersion(): ?string
    {
        return $this->sezzleHelper->getVersion();
    }
}
