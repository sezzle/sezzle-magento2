<?php

namespace Sezzle\Sezzlepay\Model;

use \Magento\Framework\HTTP\ZendClientFactory;
use \Magento\Framework\Json\Helper\Data as JsonHelper;
use \Psr\Log\LoggerInterface as Logger;
use \Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;

class Api {
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
        /** HTTP Client and afterpay config */
        $this->httpClientFactory = $httpClientFactory;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    public function call($url, $body = false, $method = \Magento\Framework\HTTP\ZendClient::GET) {
        // Client
        $client = $this->httpClientFactory->create();
        $client->setUri($url)->setRawData($this->jsonHelper->jsonEncode($body), 'application/json');

        // Set the token header
        $authToken = $this->getAuthToken();
        $client->setHeaders(array(
            'Authorization' => "Bearer $authToken",
        ));

        $requestLog = array(
            'type' => 'Request',
            'method' => $method,
            'url' => $url,
            'body' => $body
        );
        $this->logger->debug($this->jsonHelper->jsonEncode($requestLog));

        try {
            $response = $client->request($method);
            $responseLog = array(
                'type' => 'Response',
                'method' => $method,
                'url' => $url,
                'httpStatusCode' => $response->getStatus(),
                'body' => $this->jsonHelper->jsonDecode($response->getBody())
            );
            $this->logger->debug($this->jsonHelper->jsonEncode($responseLog));
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Gateway error: %1', $e->getMessage())
            );
        }
        return $response;
    }

    public function getAuthToken() {
        $method = \Magento\Framework\HTTP\ZendClient::POST;
        $url = $this->scopeConfig->getValue('payment/sezzlepay/base_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) . '/v1/authentication';
        $client = $this->httpClientFactory->create();
        $accountID = $this->scopeConfig->getValue('payment/sezzle/public_key', 'default');
		$privateKey = $this->scopeConfig->getValue('payment/sezzle/private_key', 'default');
        $body = array(
            "public_key" => $accountID,
            "private_key" => $privateKey
        );
        $client->setUri($url)->setRawData($this->jsonHelper->jsonEncode($body), 'application/json');
        $requestLog = array(
            'type' => 'Request',
            'method' => $method,
            'url' => $url,
            'body' => $body
        );
        $this->logger->debug($this->jsonHelper->jsonEncode($requestLog));
        try {
            $response = $client->request($method);
            $body = $this->jsonHelper->jsonDecode($response->getBody());
            $responseLog = array(
                'type' => 'Response',
                'method' => $method,
                'url' => $url,
                'httpStatusCode' => $response->getStatus(),
                'body' => $body
            );
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