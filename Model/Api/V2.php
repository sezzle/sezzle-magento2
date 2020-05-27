<?php


namespace Sezzle\Sezzlepay\Model\Api;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Store\Model\StoreManagerInterface;
use Sezzle\Sezzlepay\Api\Data\AuthInterface;
use Sezzle\Sezzlepay\Api\Data\AuthInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\SessionInterface;
use Sezzle\Sezzlepay\Helper\Data as SezzleHelper;
use Sezzle\Sezzlepay\Model\Config\Container\SezzleApiConfigInterface;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class V2
 * @package Sezzle\Sezzlepay\Model\Api
 */
class V2
{
    const SEZZLE_AUTH_ENDPOINT = "/v1/authentication";
    const SEZZLE_GET_ORDER_ENDPOINT = "/v2/order/%1";
    const SEZZLE_CAPTURE_ENDPOINT = "/v2/order/%1/capture";
    const SEZZLE_REFUND_ENDPOINT = "/v2/order/%1/refund";
    const SEZZLE_CREATE_SESSION_ENDPOINT = "/v2/session";
    const SEZZLE_AUTHORIZE_PAYMENT_ENDPOINT = "/v2/customer/%1/authorize";
    const SEZZLE_GET_SESSION_TOKEN_ENDPOINT = "/v2/token/%1/session";

    private $sezzleApiIdentity;
    private $apiProcessor;
    private $jsonHelper;
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
     * V2 constructor.
     * @param AuthInterfaceFactory $authFactory
     * @param DataObjectHelper $dataObjectHelper
     */
    public function __construct(
        AuthInterfaceFactory $authFactory,
        DataObjectHelper $dataObjectHelper,
        \Magento\Framework\UrlInterface $urlBuilder,
        ProcessorInterface $apiProcessor,
        SezzleApiConfigInterface $sezzleApiIdentity,
        SezzleHelper $sezzleHelper,
        JsonHelper $jsonHelper,
        Logger $logger,
        ScopeConfig $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->authFactory = $authFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->urlBuilder = $urlBuilder;
        $this->apiProcessor = $apiProcessor;
        $this->sezzleApiIdentity = $sezzleApiIdentity;
        $this->sezzleHelper = $sezzleHelper;
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     *
     */
    public function getOrder()
    {
        $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . self::SEZZLE_GET_ORDER_ENDPOINT;
        $authToken = "abcd";
        try {
            $response = $this->apiProcessor->call(
                $url,
                $authToken,
                null,
                ZendClient::GET
            );
            $body = $this->jsonHelper->jsonDecode($response);
            return $body['token'];
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway error: %1', $e->getMessage())
            );
        }
    }

    /**
     *
     * @param string $orderUUID
     * @param int $amount
     * @param bool $isPartialCapture
     * @return bool
     * @throws LocalizedException
     */
    public function captureByOrderUUID($orderUUID, $amount, $isPartialCapture)
    {
        $captureEndpoint = __(self::SEZZLE_CAPTURE_ENDPOINT, $orderUUID)->getText();
        $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . $captureEndpoint;
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
                __('Gateway error: %1', $e->getMessage())
            );
        }
    }

    /**
     *
     * @return AuthInterface
     * @throws LocalizedException
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
     * @return SessionInterface
     * @throws LocalizedException
     */
    public function createSession()
    {
        $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . self::SEZZLE_CREATE_SESSION_ENDPOINT;
        $quote = $this->checkoutSession->getQuote();
        $reference = uniqid() . "-" . $quote->getReservedOrderId();
        $body = $this->apiPayloadBuilder->buildSezzleCheckoutPayload($quote, $reference);
        /** @var SessionInterface $sessionModel */
        $sessionModel = $this->sessionFactory->create();
        try {
            $auth = $this->authenticate();
            $response = $this->apiProcessor->call(
                $url,
                $auth->getToken(),
                $body,
                ZendClient::POST
            );
            $body = $this->jsonHelper->jsonDecode($response);
            $this->dataObjectHelper->populateWithArray(
                $sessionModel,
                $body,
                SessionInterface::class
            );
            $sessionModel->setOrder($body['order']);
            $sessionModel->setTokenize($body['tokenize']);
            return $sessionModel;
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway checkout error: %1', $e->getMessage())
            );
        }
    }

    /**
     *
     */
    public function authorizePayment()
    {
        $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . self::SEZZLE_AUTHORIZE_PAYMENT_ENDPOINT;
        $authToken = "abcd";
        try {
            $response = $this->apiProcessor->call(
                $url,
                $authToken,
                null,
                ZendClient::POST
            );
            $body = $this->jsonHelper->jsonDecode($response);
            return $body['token'];
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway error: %1', $e->getMessage())
            );
        }
    }

    /**
     *
     */
    public function getCustomerUUID()
    {
        $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . self::SEZZLE_GET_SESSION_TOKEN_ENDPOINT;
        $authToken = "abcd";
        try {
            $response = $this->apiProcessor->call(
                $url,
                $authToken,
                null,
                ZendClient::GET
            );
            $body = $this->jsonHelper->jsonDecode($response);
            return $body['token'];
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway error: %1', $e->getMessage())
            );
        }
    }

    /**
     *
     */
    public function refundByOrderUUID()
    {
        $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . self::SEZZLE_REFUND_ENDPOINT;
        $authToken = "abcd";
        try {
            $response = $this->apiProcessor->call(
                $url,
                $authToken,
                null,
                ZendClient::GET
            );
            $body = $this->jsonHelper->jsonDecode($response);
            return $body['token'];
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway error: %1', $e->getMessage())
            );
        }
    }
}
