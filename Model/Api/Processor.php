<?php

namespace Sezzle\Sezzlepay\Model\Api;

use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;

/**
 * Class Processor
 * @package Sezzle\Sezzlepay\Model\Api
 */
class Processor implements ProcessorInterface
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
     * @var Config
     */
    protected $sezzleApiConfig;

    /**
     * Processor constructor.
     * @param ZendClientFactory $httpClientFactory
     * @param Config $sezzleApiConfig
     * @param JsonHelper $jsonHelper
     * @param Logger $logger
     * @param ScopeConfig $scopeConfig
     */
    public function __construct(
        ZendClientFactory $httpClientFactory,
        Config $sezzleApiConfig,
        JsonHelper $jsonHelper,
        Logger $logger,
        ScopeConfig $scopeConfig
    )
    {
        $this->httpClientFactory = $httpClientFactory;
        $this->sezzleApiConfig = $sezzleApiConfig;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Call to Sezzle Gateway
     *
     * @param $url
     * @param bool $body
     * @param $method
     * @return mixed
     */
    public function call($url, $body = false, $method = ZendClient::GET)
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($url)
            ->setRawData($this->jsonHelper->jsonEncode($body), ApiParamsInterface::CONTENT_TYPE_JSON);
        $client->setConfig(['timeout' => ApiParamsInterface::TIMEOUT]);
        try {
            $authToken = $this->sezzleApiConfig->getAuthToken();
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
}
