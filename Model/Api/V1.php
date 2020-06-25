<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Api;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Sezzle\Sezzlepay\Api\Data\AmountInterface;
use Sezzle\Sezzlepay\Api\Data\AmountInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\AuthInterface;
use Sezzle\Sezzlepay\Api\Data\AuthInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\AuthorizationInterface;
use Sezzle\Sezzlepay\Api\Data\AuthorizationInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\CustomerInterface;
use Sezzle\Sezzlepay\Api\Data\CustomerInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\LinkInterface;
use Sezzle\Sezzlepay\Api\Data\LinkInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\OrderInterface;
use Sezzle\Sezzlepay\Api\Data\OrderInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\SessionInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\SessionOrderInterface;
use Sezzle\Sezzlepay\Api\Data\SessionOrderInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\SessionTokenizeInterface;
use Sezzle\Sezzlepay\Api\Data\SessionTokenizeInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\TokenizeCustomerInterface;
use Sezzle\Sezzlepay\Api\Data\TokenizeCustomerInterfaceFactory;
use Sezzle\Sezzlepay\Api\V1Interface;
use Sezzle\Sezzlepay\Helper\Data as SezzleHelper;
use Sezzle\Sezzlepay\Model\Sezzle;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleConfigInterface;

/**
 * Class V1
 * @package Sezzle\Sezzlepay\Model\Api
 */
class V1 implements V1Interface
{
    const SEZZLE_AUTH_ENDPOINT = "/v1/authentication";
    const SEZZLE_LOGGER_ENDPOINT = "/v1/logs/%s";
    const LOG_POST_SUCCESS_MESSAGE = "File uploaded successfully";

    /**
     * @var SezzleConfigInterface
     */
    private $sezzleConfig;
    /**
     * @var ProcessorInterface
     */
    private $apiProcessor;
    /**
     * @var JsonHelper
     */
    private $jsonHelper;
    /**
     * @var SezzleHelper
     */
    private $sezzleHelper;
    /**
     * @var AuthInterfaceFactory
     */
    private $authFactory;
    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;
    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * V2 constructor.
     * @param AuthInterfaceFactory $authFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ProcessorInterface $apiProcessor
     * @param SezzleConfigInterface $sezzleConfig
     * @param SezzleHelper $sezzleHelper
     * @param JsonHelper $jsonHelper
     * @param DateTime $dateTime
     */
    public function __construct(
        AuthInterfaceFactory $authFactory,
        DataObjectHelper $dataObjectHelper,
        ProcessorInterface $apiProcessor,
        SezzleConfigInterface $sezzleConfig,
        SezzleHelper $sezzleHelper,
        JsonHelper $jsonHelper,
        DateTime $dateTime
    ) {
        $this->authFactory = $authFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->apiProcessor = $apiProcessor;
        $this->sezzleConfig = $sezzleConfig;
        $this->sezzleHelper = $sezzleHelper;
        $this->jsonHelper = $jsonHelper;
        $this->dateTime = $dateTime;
    }

    /**
     * @inheritDoc
     */
    public function authenticate()
    {
        $url = $this->sezzleConfig->getSezzleBaseUrl() . self::SEZZLE_AUTH_ENDPOINT;
        $publicKey = $this->sezzleConfig->getPublicKey();
        $privateKey = $this->sezzleConfig->getPrivateKey();
        try {
            $authModel = $this->authFactory->create();
            $body = [
                "public_key" => $publicKey,
                "private_key" => $privateKey
            ];

            $response = $this->apiProcessor->call(
                $url,
                null,
                $body,
                ZendClient::POST
            );
            $body = $this->jsonHelper->jsonDecode($response);
            $this->dataObjectHelper->populateWithArray(
                $authModel,
                $body,
                AuthInterface::class
            );
            return $authModel;
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway authentication error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function sendLogsToSezzle($merchantUUID, $log)
    {
        $logEndpoint = sprintf(self::SEZZLE_LOGGER_ENDPOINT, $merchantUUID);
        $url = $this->sezzleConfig->getSezzleBaseUrl() . $logEndpoint;
        $currentTime = $this->dateTime->date();
        $body = [
            'start_time' => $currentTime,
            'end_time' => $currentTime,
            'log' => $log
        ];
        try {
            $auth = $this->authenticate();
            $response = $this->apiProcessor->call(
                $url,
                $auth->getToken(),
                $body,
                ZendClient::POST
            );
            $body = $this->jsonHelper->jsonDecode($response);
            return isset($body['message']) && $body['message'] = self::LOG_POST_SUCCESS_MESSAGE;
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway log error: %1', $e->getMessage())
            );
        }
    }
}
