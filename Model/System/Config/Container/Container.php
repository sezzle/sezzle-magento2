<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\System\Config\Container;

use Exception;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Sezzle\Sezzlepay\Api\Data\AuthInterface;
use Sezzle\Sezzlepay\Api\Data\AuthInterfaceFactory;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\Api\ApiParamsInterface;
use Sezzle\Sezzlepay\Model\System\Config\Config;
use Sezzle\Sezzlepay\Model\System\Config\Source\Payment\GatewayRegion;

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
     * @var Config
     */
    protected $config;
    /**
     * @var GatewayRegion
     */
    protected $gatewayRegion;
    /**
     * @var Data
     */
    protected $sezzleHelper;
    /**
     * @var ResourceConfig
     */
    protected $resourceConfig;
    /**
     * @var Curl
     */
    private $curl;
    /**
     * @var AuthInterfaceFactory
     */
    private $authFactory;
    /**
     * @var JsonHelper
     */
    private $jsonHelper;
    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @param UrlInterface $urlBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Header $httpHeader
     * @param Config $config
     * @param Data $sezzleHelper
     * @param ResourceConfig $resourceConfig
     * @param Curl $curl
     * @param AuthInterfaceFactory $authFactory
     * @param JsonHelper $jsonHelper
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        UrlInterface $urlBuilder,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Header $httpHeader,
        Config $config,
        Data $sezzleHelper,
        ResourceConfig $resourceConfig,
        Curl $curl,
        AuthInterfaceFactory $authFactory,
        JsonHelper $jsonHelper,
        DataObjectHelper $dataObjectHelper
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->httpHeader = $httpHeader;
        $this->config = $config;
        $this->sezzleHelper = $sezzleHelper;
        $this->resourceConfig = $resourceConfig;
        $this->curl = $curl;
        $this->authFactory = $authFactory;
        $this->jsonHelper = $jsonHelper;
        $this->dataObjectHelper = $dataObjectHelper;
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
        return $this->scopeConfig->getValue(
            $path,
            $scope,
            $storeId
        );
    }

    /**
     * Validate API Keys
     *
     * @param bool $region
     * @param string $scope
     * @param bool|int $storeId
     * @return bool
     */
    protected function validateAPIKeys($region = false, $scope = ScopeInterface::SCOPE_STORE, $storeId = false)
    {
        $gatewayUrl = $this->getGatewayUrl(SezzleIdentity::API_VERSION_V2, $region, $scope, $storeId);
        $url = "$gatewayUrl/authentication";
        try {
            $authModel = $this->authFactory->create();
            $body = [
                "public_key" => $this->getPublicKey($storeId, $scope),
                "private_key" => $this->getPrivateKey($storeId, $scope)
            ];

            $this->curl->setTimeout(ApiParamsInterface::TIMEOUT);
            $this->curl->addHeader("Content-Type", ApiParamsInterface::CONTENT_TYPE_JSON);
            $this->curl->post($url, $this->jsonHelper->jsonEncode($body));
            $response = $this->curl->getBody();

            $body = $this->jsonHelper->jsonDecode($response);
            $this->dataObjectHelper->populateWithArray(
                $authModel,
                $body,
                AuthInterface::class
            );
            return !empty($authModel->getToken());
        } catch (Exception $e) {
            return false;
        }
    }
}
