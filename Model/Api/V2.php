<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model\Api;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Store\Model\StoreManagerInterface;
use Sezzle\Payment\Api\Data\AmountInterface;
use Sezzle\Payment\Api\Data\AmountInterfaceFactory;
use Sezzle\Payment\Api\Data\AuthInterface;
use Sezzle\Payment\Api\Data\AuthInterfaceFactory;
use Sezzle\Payment\Api\Data\AuthorizationInterface;
use Sezzle\Payment\Api\Data\AuthorizationInterfaceFactory;
use Sezzle\Payment\Api\Data\LinkInterface;
use Sezzle\Payment\Api\Data\LinkInterfaceFactory;
use Sezzle\Payment\Api\Data\OrderInterface;
use Sezzle\Payment\Api\Data\OrderInterfaceFactory;
use Sezzle\Payment\Api\Data\SessionInterfaceFactory;
use Sezzle\Payment\Api\Data\SessionOrderInterface;
use Sezzle\Payment\Api\Data\SessionOrderInterfaceFactory;
use Sezzle\Payment\Api\Data\SessionTokenizeInterface;
use Sezzle\Payment\Api\Data\SessionTokenizeInterfaceFactory;
use Sezzle\Payment\Api\Data\TokenizeCustomerInterface;
use Sezzle\Payment\Api\Data\TokenizeCustomerInterfaceFactory;
use Sezzle\Payment\Api\V2Interface;
use Sezzle\Payment\Helper\Data as SezzleHelper;
use Sezzle\Payment\Model\Sezzle;
use Sezzle\Payment\Model\System\Config\Container\SezzleApiConfigInterface;

/**
 * Class V2
 * @package Sezzle\Payment\Model\Api
 */
class V2 implements V2Interface
{
    const SEZZLE_AUTH_ENDPOINT = "/v2/authentication";
    const SEZZLE_CREATE_SESSION_ENDPOINT = "/v2/session";
    const SEZZLE_GET_ORDER_ENDPOINT = "/v2/order/%s";
    const SEZZLE_CAPTURE_BY_ORDER_UUID_ENDPOINT = "/v2/order/%s/capture";
    const SEZZLE_REFUND_BY_ORDER_UUID_ENDPOINT = "/v2/order/%s/refund";
    const SEZZLE_RELEASE_BY_ORDER_UUID_ENDPOINT = "/v2/order/%s/release";
    const SEZZLE_ORDER_CREATE_BY_CUST_UUID_ENDPOINT = "/v2/customer/%s/order";
    const SEZZLE_GET_SESSION_TOKEN_ENDPOINT = "/v2/token/%s/session";

    /**
     * @var SezzleApiConfigInterface
     */
    private $sezzleApiIdentity;
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
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var OrderInterfaceFactory
     */
    private $orderInterfaceFactory;
    /**
     * @var AuthorizationInterfaceFactory
     */
    private $authorizationInterfaceFactory;
    /**
     * @var SessionTokenizeInterfaceFactory
     */
    private $sessionTokenizeInterfaceFactory;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var PayloadBuilder
     */
    private $apiPayloadBuilder;
    /**
     * @var SessionInterfaceFactory
     */
    private $sessionInterfaceFactory;
    /**
     * @var SezzleApiConfigInterface
     */
    private $sezzleApiConfig;
    /**
     * @var SessionOrderInterfaceFactory
     */
    private $sessionOrderInterfaceFactory;
    /**
     * @var AmountInterfaceFactory
     */
    private $amountInterfaceFactory;
    /**
     * @var TokenizeCustomerInterfaceFactory
     */
    private $tokenizeCustomerInterfaceFactory;
    /**
     * @var LinkInterfaceFactory
     */
    private $linkInterfaceFactory;

    /**
     * V2 constructor.
     * @param AuthInterfaceFactory $authFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ProcessorInterface $apiProcessor
     * @param SezzleApiConfigInterface $sezzleApiIdentity
     * @param SezzleHelper $sezzleHelper
     * @param JsonHelper $jsonHelper
     * @param StoreManagerInterface $storeManager
     * @param OrderInterfaceFactory $orderInterfaceFactory
     * @param AuthorizationInterfaceFactory $authorizationInterfaceFactory
     * @param SessionTokenizeInterfaceFactory $sessionTokenizeInterfaceFactory
     * @param CheckoutSession $checkoutSession
     * @param PayloadBuilder $apiPayloadBuilder
     * @param SessionInterfaceFactory $sessionInterfaceFactory
     * @param SezzleApiConfigInterface $sezzleApiConfig
     * @param SessionOrderInterfaceFactory $sessionOrderInterfaceFactory
     * @param AmountInterfaceFactory $amountInterfaceFactory
     * @param TokenizeCustomerInterfaceFactory $tokenizeCustomerInterfaceFactory
     * @param LinkInterfaceFactory $linkInterfaceFactory
     */
    public function __construct(
        AuthInterfaceFactory $authFactory,
        DataObjectHelper $dataObjectHelper,
        ProcessorInterface $apiProcessor,
        SezzleApiConfigInterface $sezzleApiIdentity,
        SezzleHelper $sezzleHelper,
        JsonHelper $jsonHelper,
        StoreManagerInterface $storeManager,
        OrderInterfaceFactory $orderInterfaceFactory,
        AuthorizationInterfaceFactory $authorizationInterfaceFactory,
        SessionTokenizeInterfaceFactory $sessionTokenizeInterfaceFactory,
        CheckoutSession $checkoutSession,
        PayloadBuilder $apiPayloadBuilder,
        SessionInterfaceFactory $sessionInterfaceFactory,
        SezzleApiConfigInterface $sezzleApiConfig,
        SessionOrderInterfaceFactory $sessionOrderInterfaceFactory,
        AmountInterfaceFactory $amountInterfaceFactory,
        TokenizeCustomerInterfaceFactory $tokenizeCustomerInterfaceFactory,
        LinkInterfaceFactory $linkInterfaceFactory
    ) {
        $this->authFactory = $authFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->apiProcessor = $apiProcessor;
        $this->sezzleApiIdentity = $sezzleApiIdentity;
        $this->sezzleHelper = $sezzleHelper;
        $this->jsonHelper = $jsonHelper;
        $this->storeManager = $storeManager;
        $this->orderInterfaceFactory = $orderInterfaceFactory;
        $this->authorizationInterfaceFactory = $authorizationInterfaceFactory;
        $this->sessionTokenizeInterfaceFactory = $sessionTokenizeInterfaceFactory;
        $this->checkoutSession = $checkoutSession;
        $this->apiPayloadBuilder = $apiPayloadBuilder;
        $this->sessionInterfaceFactory = $sessionInterfaceFactory;
        $this->sezzleApiConfig = $sezzleApiConfig;
        $this->sessionOrderInterfaceFactory = $sessionOrderInterfaceFactory;
        $this->amountInterfaceFactory = $amountInterfaceFactory;
        $this->tokenizeCustomerInterfaceFactory = $tokenizeCustomerInterfaceFactory;
        $this->linkInterfaceFactory = $linkInterfaceFactory;
    }

    /**
     * @inheritDoc
     */
    public function authenticate()
    {
        $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . self::SEZZLE_AUTH_ENDPOINT;
        $publicKey = $this->sezzleApiIdentity->getPublicKey();
        $privateKey = $this->sezzleApiIdentity->getPrivateKey();
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
    public function createSession($reference)
    {
        $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . self::SEZZLE_CREATE_SESSION_ENDPOINT;
        $quote = $this->checkoutSession->getQuote();
        $body = $this->apiPayloadBuilder->buildSezzleCheckoutPayload($quote, $reference);
        $sessionModel = $this->sessionInterfaceFactory->create();
        try {
            $auth = $this->authenticate();
            $response = $this->apiProcessor->call(
                $url,
                $auth->getToken(),
                $body,
                ZendClient::POST
            );
            $body = $this->jsonHelper->jsonDecode($response);
            if (isset($body['order']) && ($orderObj = $body['order'])) {
                $sessionOrderModel = $this->sessionOrderInterfaceFactory->create();
                $this->dataObjectHelper->populateWithArray(
                    $sessionOrderModel,
                    $body['order'],
                    SessionOrderInterface::class
                );
                $sessionModel->setOrder($sessionOrderModel);
                $linksArray = [];
                if (isset($orderObj['links']) && is_array($orderObj['links'])) {
                    foreach ($orderObj['links'] as $link) {
                        $linksModel = $this->linkInterfaceFactory->create();
                        $this->dataObjectHelper->populateWithArray(
                            $linksModel,
                            $link,
                            LinkInterface::class
                        );
                        $linksArray[] = $linksModel;
                    }
                    $sessionModel->getOrder()->setLinks($linksArray);
                }
            }
            if (isset($body['tokenize']) && ($tokenizeObj = $body['tokenize'])) {
                $sessionTokenizeModel = $this->sessionTokenizeInterfaceFactory->create();
                $this->dataObjectHelper->populateWithArray(
                    $sessionTokenizeModel,
                    $tokenizeObj,
                    SessionTokenizeInterface::class
                );
                $sessionModel->setTokenize($sessionTokenizeModel);
                $linksArray = [];
                if (isset($tokenizeObj['links']) && is_array($tokenizeObj['links'])) {
                    foreach ($tokenizeObj['links'] as $link) {
                        $linksModel = $this->linkInterfaceFactory->create();
                        $this->dataObjectHelper->populateWithArray(
                            $linksModel,
                            $link,
                            LinkInterface::class
                        );
                        $linksArray[] = $linksModel;
                    }
                    $sessionModel->getTokenize()->setLinks($linksArray);
                }
            }
            return $sessionModel;
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway checkout error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function captureByOrderUUID($url, $orderUUID, $amount, $isPartialCapture)
    {
        if (!$url) {
            $captureEndpoint = sprintf(self::SEZZLE_CAPTURE_BY_ORDER_UUID_ENDPOINT, $orderUUID);
            $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . $captureEndpoint;
        }
        $auth = $this->authenticate();
        $payload = [
            "capture_amount" => [
                "amount_in_cents" => $amount,
                "currency" => $this->storeManager->getStore()->getCurrentCurrencyCode()
            ],
            "partial_capture" => $isPartialCapture
        ];
        try {
            $response = $this->apiProcessor->call(
                $url,
                $auth->getToken(),
                $payload,
                ZendClient::POST
            );
            $body = $this->jsonHelper->jsonDecode($response);
            return isset($body['uuid']);
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway capture error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function refundByOrderUUID($url, $orderUUID, $amount)
    {
        if (!$url) {
            $refundEndpoint = sprintf(self::SEZZLE_REFUND_BY_ORDER_UUID_ENDPOINT, $orderUUID);
            $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . $refundEndpoint;
        }
        $auth = $this->authenticate();
        $payload = [
            "amount_in_cents" => $amount,
            "currency" => $this->storeManager->getStore()->getCurrentCurrencyCode()
        ];
        try {
            $response = $this->apiProcessor->call(
                $url,
                $auth->getToken(),
                $payload,
                ZendClient::POST
            );
            $body = $this->jsonHelper->jsonDecode($response);
            return isset($body['uuid']);
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway refund error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getOrder($url, $orderUUID)
    {
        if (!$url) {
            $orderEndpoint = sprintf(self::SEZZLE_GET_ORDER_ENDPOINT, $orderUUID);
            $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . $orderEndpoint;
        }
        $auth = $this->authenticate();
        try {
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
            if (isset($body['order_amount'])) {
                /** @var AmountInterface $amountModel */
                $amountModel = $this->amountInterfaceFactory->create();
                $this->dataObjectHelper->populateWithArray(
                    $amountModel,
                    $body['order_amount'],
                    AmountInterface::class
                );
                $orderModel->setOrderAmount($amountModel);
            }
            if (isset($body['authorization'])) {
                $this->sezzleHelper->logSezzleActions($body);
                $authorizationModel = $this->authorizationInterfaceFactory->create();
                $this->dataObjectHelper->populateWithArray(
                    $authorizationModel,
                    $body['authorization'],
                    AuthorizationInterface::class
                );
                $orderModel->setAuthorization($authorizationModel);
            }
            return $orderModel;
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway order error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function createOrderByCustomerUUID($url, $customerUUID, $amount)
    {
        $quote = $this->checkoutSession->getQuote();
        $reference = $quote->getPayment()->getAdditionalInformation(Sezzle::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID);
        if (!$url) {
            $authorizeEndpoint = sprintf(self::SEZZLE_ORDER_CREATE_BY_CUST_UUID_ENDPOINT, $customerUUID);
            $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . $authorizeEndpoint;
        }
        $auth = $this->authenticate();
        $payload = [
            "intent" => 'AUTH',
            "reference_id" => $reference,
            "order_amount" => [
                "amount_in_cents" => $amount,
                "currency" => $this->storeManager->getStore()->getCurrentCurrencyCode()
            ]
        ];
        try {
            $response = $this->apiProcessor->call(
                $url,
                $auth->getToken(),
                $payload,
                ZendClient::POST
            );
            $body = $this->jsonHelper->jsonDecode($response);
            $authorizationModel = $this->authorizationInterfaceFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $authorizationModel,
                $body,
                AuthorizationInterface::class
            );
            $linksArray = [];
            if (isset($body['links']) && is_array($body['links'])) {
                foreach ($body['links'] as $link) {
                    $linksModel = $this->linkInterfaceFactory->create();
                    $this->dataObjectHelper->populateWithArray(
                        $linksModel,
                        $link,
                        LinkInterface::class
                    );
                    $linksArray[] = $linksModel;
                }
                $authorizationModel->setLinks($linksArray);
            }
            return $authorizationModel;
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway create order error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getTokenDetails($url, $token)
    {
        $sessionTokenEndpoint = sprintf(self::SEZZLE_GET_SESSION_TOKEN_ENDPOINT, $token);
        if (!$url) {
            $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . $sessionTokenEndpoint;
        }
        $auth = $this->authenticate();
        try {
            $response = $this->apiProcessor->call(
                $url,
                $auth->getToken(),
                null,
                ZendClient::GET
            );
            $body = $this->jsonHelper->jsonDecode($response);
            /** @var SessionTokenizeInterface $sessionTokenizeModel */
            $tokenizeCustomerModel = $this->tokenizeCustomerInterfaceFactory->create();
            if (isset($body['customer'])) {
                $this->dataObjectHelper->populateWithArray(
                    $tokenizeCustomerModel,
                    $body['customer'],
                    TokenizeCustomerInterface::class
                );
                $linksArray = [];
                if (isset($body['customer']['links']) && is_array($body['customer']['links'])) {
                    foreach ($body['customer']['links'] as $link) {
                        $linksModel = $this->linkInterfaceFactory->create();
                        $this->dataObjectHelper->populateWithArray(
                            $linksModel,
                            $link,
                            LinkInterface::class
                        );
                        $linksArray[] = $linksModel;
                    }
                    $tokenizeCustomerModel->setLinks($linksArray);
                }
            }
            return $tokenizeCustomerModel;
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway get token error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function releasePaymentByOrderUUID($url, $orderUUID, $amount)
    {
        if (!$url) {
            $releaseEndpoint = sprintf(self::SEZZLE_RELEASE_BY_ORDER_UUID_ENDPOINT, $orderUUID);
            $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . $releaseEndpoint;
        }
        $auth = $this->authenticate();
        $payload = [
            "amount_in_cents" => $amount,
            "currency" => $this->storeManager->getStore()->getCurrentCurrencyCode()
        ];
        try {
            $response = $this->apiProcessor->call(
                $url,
                $auth->getToken(),
                $payload,
                ZendClient::POST
            );
            $body = $this->jsonHelper->jsonDecode($response);
            return isset($body['uuid']);
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway release payment error: %1', $e->getMessage())
            );
        }
    }
}
