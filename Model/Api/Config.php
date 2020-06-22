<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model\Api;

use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Psr\Log\LoggerInterface as Logger;
use Sezzle\Payment\Helper\Data as SezzleHelper;
use Sezzle\Payment\Model\System\Config\Container\SezzleApiConfigInterface;

/**
 * Class Config
 * @package Sezzle\Payment\Model\Api
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
     * @var SezzleHelper
     */
    protected $sezzleHelper;

    /**
     * Config constructor.
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param ProcessorInterface $apiProcessor
     * @param SezzleApiConfigInterface $sezzleApiIdentity
     * @param SezzleHelper $sezzleHelper
     * @param JsonHelper $jsonHelper
     * @param Logger $logger
     * @param ScopeConfig $scopeConfig
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        ProcessorInterface $apiProcessor,
        SezzleApiConfigInterface $sezzleApiIdentity,
        SezzleHelper $sezzleHelper,
        JsonHelper $jsonHelper,
        Logger $logger,
        ScopeConfig $scopeConfig
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->apiProcessor = $apiProcessor;
        $this->sezzleApiIdentity = $sezzleApiIdentity;
        $this->sezzleHelper = $sezzleHelper;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get auth token
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
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
            $this->sezzleHelper->logSezzleActions($e->getMessage());
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
        return $this->urlBuilder->getUrl("sezzle/payment/complete/id/$orderId/magento_sezzle_id/$reference", ['_secure' => true]);
    }

    /**
     * Get cancel url
     * @return mixed
     */
    public function getCancelUrl()
    {
        return $this->urlBuilder->getUrl("sezzle/payment/cancel/", ['_secure' => true]);
    }
}
