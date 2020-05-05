<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Gateway;

use Sezzle\Sezzlepay\Helper\Data as SezzleHelper;
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
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var SezzleHelper
     */
    private $sezzleHelper;

    /**
     * Heartbeat constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param SezzleHelper $sezzleHelper
     * @param ConfigInterface $config
     * @param \Sezzle\Sezzlepay\Model\Api\ProcessorInterface $sezzleApiProcessor
     * @param SezzleApiConfigInterface $sezzleApiConfig
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        SezzleHelper $sezzleHelper,
        ConfigInterface $config,
        \Sezzle\Sezzlepay\Model\Api\ProcessorInterface $sezzleApiProcessor,
        SezzleApiConfigInterface $sezzleApiConfig
    ) {
        $this->sezzleApiConfig = $sezzleApiConfig;
        $this->sezzleHelper = $sezzleHelper;
        $this->config = $config;
        $this->sezzleApiProcessor = $sezzleApiProcessor;
        $this->logger = $logger;
    }

    /**
     * Sending hearbeat to Sezzle
     */
    public function send()
    {
        $this->sezzleHelper->logSezzleActions("****Hearbeat process start****");
        $isPublicKeyEntered = $this->sezzleApiConfig->getPublicKey() ? true : false;
        $isPrivateKeyEntered = $this->sezzleApiConfig->getPrivateKey() ? true : false;
        $isWidgetConfiguredForPDP = $this->sezzleApiConfig->isWidgetScriptAllowedForPDP() ? true : false;
        $isWidgetConfiguredForCartPage = $this->sezzleApiConfig->isWidgetScriptAllowedForCartPage() ? true : false;
        $isMerchantIdEntered = $this->sezzleApiConfig->getMerchantId() ? true : false;
        $isPaymentMethodActive = $this->sezzleApiConfig->isEnabled() ? true : false;

        $body = [
            'is_payment_active' => $isPaymentMethodActive,
            'is_widget_active' => true,
            'is_widget_configured' => $isWidgetConfiguredForPDP && $isWidgetConfiguredForCartPage,
            'is_merchant_id_entered' => $isMerchantIdEntered,
            'merchant_id' => $this->sezzleApiConfig->getMerchantId()
        ];

        if ($isPublicKeyEntered && $isPrivateKeyEntered && $isMerchantIdEntered) {
            $url = $this->sezzleApiConfig->getSezzleBaseUrl() . '/v1/merchant_data' . '/magento/heartbeat';
            try {
                $authToken = $this->config->getAuthToken();
                $this->sezzleApiProcessor->call(
                    $url,
                    $authToken,
                    $body,
                    \Magento\Framework\HTTP\ZendClient::POST
                );
                $this->sezzleHelper->logSezzleActions("****Hearbeat process end****");
            } catch (\Exception $e) {
                $this->sezzleHelper->logSezzleActions("Error while sending heartbeat to Sezzle" . $e->getMessage());
            }
        } else {
            $this->sezzleHelper->logSezzleActions('Could not send Heartbeat to Sezzle. Please set api keys.');
        }
    }
}
