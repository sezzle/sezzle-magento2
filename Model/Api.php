<?php

namespace Sezzle\Sezzlepay\Model;

use \Magento\Framework\HTTP\ZendClientFactory;
use \Magento\Framework\Json\Helper\Data as JsonHelper;
use \Sezzle\Sezzlepay\Model\SezzlePaymentMethod as SezzlepayModel;
use \Psr\Log\LoggerInterface as Logger;

class Api {
    protected $client;
    protected $jsonHelper;
    protected $sezzlepayModel;
    protected $logger;

    public function __construct(
        ZendClientFactory $httpClientFactory,
        SezzlepayModel $sezzlepayModel,
        JsonHelper $jsonHelper,
        Logger $logger
    ) {
        /** HTTP Client and afterpay config */
        $this->httpClientFactory = $httpClientFactory;
        $this->sezzlepayModel = $sezzlepayModel;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
    }

    public function call($url, $body = false, $method = \Magento\Framework\HTTP\ZendClient::GET) {
        // Client
        $client = $this->httpClientFactory->create();
        $client->setUri($url)->setRawData($this->jsonHelper->jsonEncode($body), 'application/json');

        // Set the token header
        $authToken = $this->getAuthToken();
        $client->setHeader('Authorization', "Bearer $authToken");

        
    }
}