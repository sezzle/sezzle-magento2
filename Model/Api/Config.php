<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 * @license     https://www.sezzle.com/LICENSE.txt
 */

namespace Sezzle\Sezzlepay\Model\Api;

use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Psr\Log\LoggerInterface as Logger;
use Sezzle\Sezzlepay\Model\Config\Container\SezzleApiConfigInterface;


/**
 * Class Config
 * @package Sezzle\Sezzlepay\Model\Api
 */
class Config implements ConfigInterface
{
    /**
     * @var JsonHelper
     */
    protected $jsonHelper;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var ScopeConfig
     */
    protected $scopeConfig;
    /**
     * @var SezzleApiConfigInterface
     */
    protected $sezzleApiIdentity;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var ProcessorInterface
     */
    protected $apiProcessor;

    /**
     * Config constructor.
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param ProcessorInterface $apiProcessor
     * @param SezzleApiConfigInterface $sezzleApiIdentity
     * @param JsonHelper $jsonHelper
     * @param Logger $logger
     * @param ScopeConfig $scopeConfig
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        ProcessorInterface $apiProcessor,
        SezzleApiConfigInterface $sezzleApiIdentity,
        JsonHelper $jsonHelper,
        Logger $logger,
        ScopeConfig $scopeConfig
    )
    {
        $this->_urlBuilder = $urlBuilder;
        $this->apiProcessor = $apiProcessor;
        $this->sezzleApiIdentity = $sezzleApiIdentity;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get auth token
     * @return mixed
     */
    public function getAuthToken()
    {
        $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . '/v1/authentication';
        $publicKey = $this->sezzleApiIdentity->getPublicKey();
        $privateKey = $this->sezzleApiIdentity->getPrivateKey();
        $body = [
            "public_key" => $publicKey,
            "private_key" => $privateKey
        ];
        $requestLog = [
            'type' => 'Request',
            'method' => ZendClient::POST,
            'url' => $url,
            'body' => $body
        ];
        $this->logger->debug($this->jsonHelper->jsonEncode($requestLog));
        try {
            $response = $this->apiProcessor->call(
                $url,
                null,
                $body,
                ZendClient::POST
            );
            $body = $this->jsonHelper->jsonDecode($response);
            return $body['token'];
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Gateway error: %1', $e->getMessage())
            );
        }
    }

    /**
     * Get complete url
     * @param $orderId
     * @param $reference
     * @return mixed
     */
    public function getCompleteUrl($orderId, $reference)
    {
        return $this->_urlBuilder->getUrl("sezzlepay/standard/complete/id/$orderId/magento_sezzle_id/$reference", ['_secure' => true]);
    }

    /**
     * Get cancel url
     * @return mixed
     */
    public function getCancelUrl()
    {
        return $this->_urlBuilder->getUrl("sezzlepay/standard/cancel/", ['_secure' => true]);
    }
}
