<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\System\Config\Container;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Header;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Container
 * @package Sezzle\Sezzlepay\Model\System\Config\Container
 */
abstract class Container implements IdentityInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Core store config
     *
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Store
     */
    protected $store;

    /**
     * @var string
     */
    protected $customerName;

    /**
     * @var string
     */
    protected $customerEmail;
    /**
     * @var UrlInterface
     */
    public $urlBuilder;
    /**
     * @var Header
     */
    protected $httpHeader;

    /**
     * @param UrlInterface $urlBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Header $httpHeader
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Header $httpHeader
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->httpHeader = $httpHeader;
    }

    /**
     * Return store
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function getStore()
    {
        //current store
        if ($this->store instanceof Store) {
            return $this->store;
        }
        return $this->storeManager->getStore();
    }

    /**
     * Set current store
     *
     * @param Store $store
     * @return void
     */
    public function setStore(Store $store)
    {
        $this->store = $store;
    }

    /**
     * Get config value
     *
     * @param string $path
     * @param string $storeId
     * @param null|int|string $scope
     * @return mixed
     */
    protected function getConfigValue($path, $storeId, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig-> getValue(
            $path,
            $scope,
            $storeId
        );
    }
}
