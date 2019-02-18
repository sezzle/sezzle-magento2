<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 * @license     https://www.sezzle.com/LICENSE.txt
 */

namespace Sezzle\Sezzlepay\Model\Gateway;

use Sezzle\Sezzlepay\Model\Config\Container\ProductWidgetConfigInterface;
use Sezzle\Sezzlepay\Model\Api\ConfigInterface;
use Sezzle\Sezzlepay\Model\Config\Container\SezzleApiConfigInterface;

/**
 * Class Heartbeat
 * @package Sezzle\Sezzlepay\Model\Gateway
 */
class Heartbeat
{
    /**
     * @var SezzleApiConfigInterface
     */
    private $sezzleApiConfig;
    /**
     * @var \Sezzle\Sezzlepay\Model\Api\ProcessorInterface
     */
    private $sezzleApiProcessor;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
    /**
     * @var ProductWidgetConfigInterface
     */
    private $productWidgetConfig;
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * Heartbeat constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param ConfigInterface $config
     * @param \Sezzle\Sezzlepay\Model\Api\ProcessorInterface $sezzleApiProcessor
     * @param SezzleApiConfigInterface $sezzleApiConfig
     * @param ProductWidgetConfigInterface $productWidgetConfig
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ConfigInterface $config,
        \Sezzle\Sezzlepay\Model\Api\ProcessorInterface $sezzleApiProcessor,
        SezzleApiConfigInterface $sezzleApiConfig,
        ProductWidgetConfigInterface $productWidgetConfig
    )
    {
        $this->sezzleApiConfig = $sezzleApiConfig;
        $this->config = $config;
        $this->productWidgetConfig = $productWidgetConfig;
        $this->sezzleApiProcessor = $sezzleApiProcessor;
        $this->logger = $logger;
    }

    /**
     * Sending hearbeat to Sezzle
     */
    public function send()
    {
        $isPublicKeyEntered = $this->sezzleApiConfig->getPublicKey() ? true : false;
        $isPrivateKeyEntered = $this->sezzleApiConfig->getPrivateKey() ? true : false;
        $isWidgetConfigured = $this->productWidgetConfig->getTargetXPath() ? true : false;
        $isMerchantIdEntered = $this->sezzleApiConfig->getMerchantId() ? true : false;
        $isPaymentMethodActive = $this->sezzleApiConfig->isEnabled() ? true : false;

        $body = [
            'is_payment_active' => $isPaymentMethodActive,
            'is_widget_active' => true,
            'is_widget_configured' => $isWidgetConfigured,
            'is_merchant_id_entered' => $isMerchantIdEntered,
            'merchant_id' => $this->sezzleApiConfig->getMerchantId()
        ];

        if ($isPublicKeyEntered && $isPrivateKeyEntered && $isMerchantIdEntered) {
            $url = $this->sezzleApiConfig->getSezzleBaseUrl() . '/v1/merchant_data' . '/magento/heartbeat';
            try {
                $authToken = $this->config->getAuthToken();
                $response = $this->sezzleApiProcessor->call(
                    $url,
                    $authToken,
                    $body,
                    \Magento\Framework\HTTP\ZendClient::POST
                );
                $this->logger->debug(print_r($response));
            } catch (\Exception $e) {
                $this->logger->debug("Error while sending heartbeat to Sezzle" . $e->getMessage());
            }
        } else {
            $this->logger->debug('Could not send Heartbeat to Sezzle. Please set api keys.');
        }
    }
}
