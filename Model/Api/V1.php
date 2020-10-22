<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Api;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Sezzle\Sezzlepay\Api\Data\AuthInterface;
use Sezzle\Sezzlepay\Api\Data\AuthInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\OrderInterface;
use Sezzle\Sezzlepay\Api\Data\OrderInterfaceFactory;
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
    const SEZZLE_CAPTURE_ENDPOINT = "/v1/checkouts/%s/complete";
    const SEZZLE_REFUND_ENDPOINT = "/v1/orders/%s/refund";
    const SEZZLE_GET_ORDER_ENDPOINT = "/v1/orders/%s";
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
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var OrderInterfaceFactory
     */
    private $orderInterfaceFactory;

    /**
     * V1 constructor.
     * @param AuthInterfaceFactory $authFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ProcessorInterface $apiProcessor
     * @param SezzleConfigInterface $sezzleConfig
     * @param SezzleHelper $sezzleHelper
     * @param JsonHelper $jsonHelper
     * @param DateTime $dateTime
     * @param StoreManagerInterface $storeManager
     * @param OrderInterfaceFactory $orderInterfaceFactory
     */
    public function __construct(
        AuthInterfaceFactory $authFactory,
        DataObjectHelper $dataObjectHelper,
        ProcessorInterface $apiProcessor,
        SezzleConfigInterface $sezzleConfig,
        SezzleHelper $sezzleHelper,
        JsonHelper $jsonHelper,
        DateTime $dateTime,
        StoreManagerInterface $storeManager,
        OrderInterfaceFactory $orderInterfaceFactory
    ) {
        $this->authFactory = $authFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->apiProcessor = $apiProcessor;
        $this->sezzleConfig = $sezzleConfig;
        $this->sezzleHelper = $sezzleHelper;
        $this->jsonHelper = $jsonHelper;
        $this->dateTime = $dateTime;
        $this->storeManager = $storeManager;
        $this->orderInterfaceFactory = $orderInterfaceFactory;
    }

    /**
     * Authenticate user
     *
     * @return AuthInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function authenticate()
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
                __('V1 Gateway authentication error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function capture($orderReferenceID)
    {
        $captureEndpoint = sprintf(self::SEZZLE_CAPTURE_ENDPOINT, $orderReferenceID);
        $url = $this->sezzleConfig->getSezzleBaseUrl() . $captureEndpoint;
        try {
            $auth = $this->authenticate();
            $response = $this->apiProcessor->call(
                $url,
                $auth->getToken(),
                null,
                ZendClient::POST
            );
            $body = $this->jsonHelper->jsonDecode($response);
            return isset($body['captured_at']) && $body['captured_at'];
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('V1 Gateway capture error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function refund($orderReferenceID, $amount)
    {
        $refundEndpoint = sprintf(self::SEZZLE_REFUND_ENDPOINT, $orderReferenceID);
        $url = $this->sezzleConfig->getSezzleBaseUrl() . $refundEndpoint;
        $payload = [
            "amount" => [
                "amount_in_cents" => $amount,
                "currency" => $this->storeManager->getStore()->getCurrentCurrencyCode()
            ]
        ];
        try {
            $auth = $this->authenticate();
            $response = $this->apiProcessor->call(
                $url,
                $auth->getToken(),
                $payload,
                ZendClient::POST
            );
            $body = $this->jsonHelper->jsonDecode($response);
            return (isset($body['refund_id']) && $body['refund_id']) ? $body['refund_id'] : "";
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('V1 Gateway refund error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getOrder($orderReferenceID)
    {
        $orderEndpoint = sprintf(self::SEZZLE_GET_ORDER_ENDPOINT, $orderReferenceID);
        $url = $this->sezzleConfig->getSezzleBaseUrl() . $orderEndpoint;
        try {
            $auth = $this->authenticate();
            $response = $this->apiProcessor->call(
                $url,
                $auth->getToken(),
                null,
                ZendClient::GET
            );
            $body = $this->jsonHelper->jsonDecode($response);
            $orderModel = $this->orderInterfaceFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $orderModel,
                $body,
                OrderInterface::class
            );
            return $orderModel;
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('V1 Gateway get order error: %1', $e->getMessage())
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
                __('V1 Gateway log error: %1', $e->getMessage())
            );
        }
    }
}
