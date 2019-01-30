<?php

namespace Sezzle\Sezzlepay\Model\Api;

use Sezzle\Sezzlepay\Model\Config\Container\SezzleApiConfigInterface;


class Config
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
     * Processor constructor.
     * @param ZendClientFactory $httpClientFactory
     * @param SezzleApiConfigInterface $sezzleApiIdentity
     * @param JsonHelper $jsonHelper
     * @param Logger $logger
     * @param ScopeConfig $scopeConfig
     */
    public function __construct(
        ZendClientFactory $httpClientFactory,
        SezzleApiConfigInterface $sezzleApiIdentity,
        JsonHelper $jsonHelper,
        Logger $logger,
        ScopeConfig $scopeConfig
    )
    {
        $this->httpClientFactory = $httpClientFactory;
        $this->sezzleApiIdentity = $sezzleApiIdentity;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    public function getAuthToken()
    {
        $method = \Magento\Framework\HTTP\ZendClient::POST;
        $url = $this->sezzleApiIdentity->getSezzleBaseUrl();
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

    public function getCompleteUrl($orderId, $reference)
    {
        return $this->_urlBuilder->getUrl("sezzlepay/standard/complete/id/$orderId/magento_sezzle_id/$reference", ['_secure' => true]);
    }

    public function getCancelUrl()
    {
        return $this->_urlBuilder->getUrl("sezzlepay/standard/cancel/", ['_secure' => true]);
    }
}