<?php


namespace Sezzle\Sezzlepay\Model\Api;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Store\Model\StoreManagerInterface;
use Sezzle\Sezzlepay\Api\Data\AmountInterface;
use Sezzle\Sezzlepay\Api\Data\AmountInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\AuthInterface;
use Sezzle\Sezzlepay\Api\Data\AuthInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\AuthorizationInterface;
use Sezzle\Sezzlepay\Api\Data\AuthorizationInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\OrderInterface;
use Sezzle\Sezzlepay\Api\Data\OrderInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\SessionInterface;
use Sezzle\Sezzlepay\Api\Data\SessionInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\SessionOrderInterface;
use Sezzle\Sezzlepay\Api\Data\SessionOrderInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\SessionTokenizeInterface;
use Sezzle\Sezzlepay\Api\Data\SessionTokenizeInterfaceFactory;
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Helper\Data as SezzleHelper;
use Sezzle\Sezzlepay\Model\Config\Container\SezzleApiConfigInterface;
use Sezzle\Sezzlepay\Model\SezzlePay;

/**
 * Class V2
 * @package Sezzle\Sezzlepay\Model\Api
 */
class V2 implements V2Interface
{
    const SEZZLE_AUTH_ENDPOINT = "/v2/authentication";
    const SEZZLE_GET_ORDER_ENDPOINT = "/v2/order/%s";
    const SEZZLE_CAPTURE_ENDPOINT = "/v2/order/%s/capture";
    const SEZZLE_REFUND_ENDPOINT = "/v2/order/%s/refund";
    const SEZZLE_CREATE_SESSION_ENDPOINT = "/v2/session";
    const SEZZLE_AUTHORIZE_PAYMENT_ENDPOINT = "/v2/customer/%s/authorize";
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
        AmountInterfaceFactory $amountInterfaceFactory
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
        /** @var SessionInterface $sessionModel */
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
            if (isset($body['order'])) {
                $sessionOrderModel = $this->sessionOrderInterfaceFactory->create();
                $this->dataObjectHelper->populateWithArray(
                    $sessionOrderModel,
                    $body['order'],
                    SessionOrderInterface::class
                );
                $sessionModel->setOrder($sessionOrderModel);
            }
            if (isset($body['tokenize'])) {
                $sessionTokenizeModel = $this->sessionTokenizeInterfaceFactory->create();
                $this->dataObjectHelper->populateWithArray(
                    $sessionTokenizeModel,
                    $body['tokenize'],
                    SessionTokenizeInterface::class
                );
                $sessionModel->setTokenize($sessionTokenizeModel);
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
    public function captureByOrderUUID($orderUUID, $amount, $isPartialCapture)
    {
        $captureEndpoint = sprintf(self::SEZZLE_CAPTURE_ENDPOINT, $orderUUID);
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
                __('Gateway capture error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function refundByOrderUUID($orderUUID, $amount)
    {
        $refundEndpoint = sprintf(self::SEZZLE_REFUND_ENDPOINT, $orderUUID);
        $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . $refundEndpoint;
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
    public function getOrder($orderUUID)
    {
        $orderEndpoint = sprintf(self::SEZZLE_GET_ORDER_ENDPOINT, $orderUUID);
        $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . $orderEndpoint;
        $auth = $this->authenticate();
        try {
            $response = $this->apiProcessor->call(
                $url,
                $auth->getToken(),
                null,
                ZendClient::GET
            );
            $body = $this->jsonHelper->jsonDecode($response);
            /** @var OrderInterface $orderModel */
            $orderModel = $this->orderInterfaceFactory->create();
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
            $this->dataObjectHelper->populateWithArray(
                $orderModel,
                $body,
                OrderInterface::class
            );
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
    public function authorizePayment($customerUUID, $amount)
    {
        $quote = $this->checkoutSession->getQuote();
        $reference = uniqid() . "-" . $quote->getReservedOrderId();
        $doCapture = $this->sezzleApiConfig->getPaymentAction() == SezzlePay::ACTION_AUTHORIZE_CAPTURE;
        $authorizeEndpoint = sprintf(self::SEZZLE_AUTHORIZE_PAYMENT_ENDPOINT, $customerUUID);
        $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . $authorizeEndpoint;
        $auth = $this->authenticate();
        $payload = [
            "reference_id" => $reference,
            "payment_amount" => [
                "amount_in_cents" => $amount,
                "currency" => $this->storeManager->getStore()->getCurrentCurrencyCode()
            ],
            "capture" => $doCapture
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
            return $authorizationModel;
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway authorize payment error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getCustomerUUID($token)
    {
        $sessionTokenEndpoint = sprintf(self::SEZZLE_GET_SESSION_TOKEN_ENDPOINT, $token);
        $url = $this->sezzleApiIdentity->getSezzleBaseUrl() . $sessionTokenEndpoint;
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
            $sessionTokenizeModel = $this->sessionTokenizeInterfaceFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $sessionTokenizeModel,
                $body,
                SessionTokenizeInterface::class
            );
            return $sessionTokenizeModel->getCustomer()->getUuid();
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway get customer uuid error: %1', $e->getMessage())
            );
        }
    }
}
