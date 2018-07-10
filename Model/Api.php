<?php

namespace Sezzle\Sezzlepay\Model;

use \Magento\Framework\HTTP\ZendClientFactory;
use \Magento\Framework\Json\Helper\Data as JsonHelper;
use \Psr\Log\LoggerInterface as Logger;
use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;

class Api
{
    protected $client;
    protected $jsonHelper;
    protected $logger;
    protected $scopeConfig;

    public function __construct(
        ZendClientFactory $httpClientFactory,
        JsonHelper $jsonHelper,
        Logger $logger,
        ScopeConfig $scopeConfig
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    public function call($url, $body = false, $method = \Magento\Framework\HTTP\ZendClient::GET)
    {
        // Client
        $client = $this->httpClientFactory->create();
        $client->setUri($url)->setRawData($this->jsonHelper->jsonEncode($body), 'application/json');
        $client->setConfig(['timeout' => 80]);

        // Set the token header
        $authToken = $this->getAuthToken();
        $client->setHeaders([
            'Authorization' => "Bearer $authToken",
        ]);

        $requestLog = [
            'type' => 'Request',
            'method' => $method,
            'url' => $url,
            'body' => $body
        ];
        $this->logger->debug($this->jsonHelper->jsonEncode($requestLog));

        try {
            $response = $client->request($method);
            $responseLog = [
                'type' => 'Response',
                'method' => $method,
                'url' => $url,
                'httpStatusCode' => $response->getStatus(),
            ];
            $this->logger->debug($this->jsonHelper->jsonEncode($responseLog));
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Gateway error: %1', $e->getMessage())
            );
        }
        return $response;
    }

    public function getAuthToken()
    {
        $method = \Magento\Framework\HTTP\ZendClient::POST;
        $url = $this->scopeConfig->getValue('payment/sezzlepay/base_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) . '/v1/authentication';
        $client = $this->httpClientFactory->create();
        $accountID = $this->scopeConfig->getValue('payment/sezzlepay/public_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $privateKey = $this->scopeConfig->getValue('payment/sezzlepay/private_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $body = [
            "public_key" => $accountID,
            "private_key" => $privateKey
        ];
        $client->setUri($url)->setRawData($this->jsonHelper->jsonEncode($body), 'application/json');
        $client->setConfig(['timeout' => 80]);
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
}
