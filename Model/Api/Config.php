<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 * @license     https://www.sezzle.com/LICENSE.txt
 */

namespace Sezzle\Sezzlepay\Model\Api;

use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\Http\ZendClient;
use Magento\Framework\Http\ZendClientFactory;
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
     * @var ZendClientFactory
     */
    protected $httpClientFactory;
    /**
     * @var SezzleApiConfigInterface
     */
    protected $sezzleApiIdentity;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * Processor constructor.
     * @param ZendClientFactory $httpClientFactory
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param SezzleApiConfigInterface $sezzleApiIdentity
     * @param JsonHelper $jsonHelper
     * @param Logger $logger
     * @param ScopeConfig $scopeConfig
     */
    public function __construct(
        ZendClientFactory $httpClientFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        SezzleApiConfigInterface $sezzleApiIdentity,
        JsonHelper $jsonHelper,
        Logger $logger,
        ScopeConfig $scopeConfig
    )
    {
        $this->httpClientFactory = $httpClientFactory;
        $this->_urlBuilder = $urlBuilder;
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
        $method = ZendClient::POST;
        $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . '/v1/authentication';
        $client = $this->httpClientFactory->create();
        $publicKey = $this->sezzleApiIdentity->getPublicKey();
        $privateKey = $this->sezzleApiIdentity->getPrivateKey();
        $body = [
            "public_key" => $publicKey,
            "private_key" => $privateKey
        ];
        $client->setUri($url)
            ->setRawData($this->jsonHelper->jsonEncode($body), ApiParamsInterface::CONTENT_TYPE_JSON);
        $client->setConfig(['timeout' => ApiParamsInterface::TIMEOUT]);
        $requestLog = [
            'type' => 'Request',
            'method' => $method,
            'url' => $url,
            'body' => $body
        ];
        $this->logger->debug($this->jsonHelper->jsonEncode($requestLog));
        try {
            $response = $client->request($method);
            $body = $this->jsonHelper->jsonDecode($response->getBody());
            $responseLog = [
                'type' => 'Response',
                'method' => $method,
                'url' => $url,
                'httpStatusCode' => $response->getStatus(),
                'body' => $body
            ];
            $this->logger->debug($this->jsonHelper->jsonEncode($responseLog));
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