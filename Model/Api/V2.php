<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Api;

use DateInterval;
use Exception;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
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
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Helper\Data as SezzleHelper;
use Sezzle\Sezzlepay\Model\Sezzle;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleConfigInterface;

/**
 * Class V2
 * @package Sezzle\Sezzlepay\Model\Api
 */
class V2 implements V2Interface
{
    const SEZZLE_AUTH_ENDPOINT = "/authentication";
    const SEZZLE_CREATE_SESSION_ENDPOINT = "/session";
    const SEZZLE_GET_ORDER_ENDPOINT = "/order/%s";
    const SEZZLE_GET_CUSTOMER_ENDPOINT = "/customer/%s";
    const SEZZLE_CAPTURE_BY_ORDER_UUID_ENDPOINT = "/order/%s/capture";
    const SEZZLE_REFUND_BY_ORDER_UUID_ENDPOINT = "/order/%s/refund";
    const SEZZLE_RELEASE_BY_ORDER_UUID_ENDPOINT = "/order/%s/release";
    const SEZZLE_REAUTHORIZE_ORDER_UUID_ENDPOINT = "/order/%s/reauthorize";
    const SEZZLE_ORDER_CREATE_BY_CUST_UUID_ENDPOINT = "/customer/%s/order";
    const SEZZLE_GET_SESSION_TOKEN_ENDPOINT = "/token/%s/session";

    const SEZZLE_GET_SETTLEMENT_SUMMARIES_ENDPOINT = "/settlements/summaries";
    const SEZZLE_GET_SETTLEMENT_DETAILS_ENDPOINT = "/settlements/details/%s";

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
     * @var CustomerInterfaceFactory
     */
    private $customerInterfaceFactory;
    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * V2 constructor.
     * @param AuthInterfaceFactory $authFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ProcessorInterface $apiProcessor
     * @param SezzleHelper $sezzleHelper
     * @param JsonHelper $jsonHelper
     * @param StoreManagerInterface $storeManager
     * @param OrderInterfaceFactory $orderInterfaceFactory
     * @param AuthorizationInterfaceFactory $authorizationInterfaceFactory
     * @param SessionTokenizeInterfaceFactory $sessionTokenizeInterfaceFactory
     * @param CheckoutSession $checkoutSession
     * @param PayloadBuilder $apiPayloadBuilder
     * @param SessionInterfaceFactory $sessionInterfaceFactory
     * @param SezzleConfigInterface $sezzleConfig
     * @param SessionOrderInterfaceFactory $sessionOrderInterfaceFactory
     * @param AmountInterfaceFactory $amountInterfaceFactory
     * @param TokenizeCustomerInterfaceFactory $tokenizeCustomerInterfaceFactory
     * @param LinkInterfaceFactory $linkInterfaceFactory
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        AuthInterfaceFactory $authFactory,
        DataObjectHelper $dataObjectHelper,
        ProcessorInterface $apiProcessor,
        SezzleHelper $sezzleHelper,
        JsonHelper $jsonHelper,
        StoreManagerInterface $storeManager,
        OrderInterfaceFactory $orderInterfaceFactory,
        AuthorizationInterfaceFactory $authorizationInterfaceFactory,
        SessionTokenizeInterfaceFactory $sessionTokenizeInterfaceFactory,
        CheckoutSession $checkoutSession,
        PayloadBuilder $apiPayloadBuilder,
        SessionInterfaceFactory $sessionInterfaceFactory,
        SezzleConfigInterface $sezzleConfig,
        SessionOrderInterfaceFactory $sessionOrderInterfaceFactory,
        AmountInterfaceFactory $amountInterfaceFactory,
        TokenizeCustomerInterfaceFactory $tokenizeCustomerInterfaceFactory,
        LinkInterfaceFactory $linkInterfaceFactory,
        CustomerInterfaceFactory $customerInterfaceFactory,
        TimezoneInterface $timezone
    ) {
        $this->authFactory = $authFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->apiProcessor = $apiProcessor;
        $this->sezzleConfig = $sezzleConfig;
        $this->sezzleHelper = $sezzleHelper;
        $this->jsonHelper = $jsonHelper;
        $this->storeManager = $storeManager;
        $this->orderInterfaceFactory = $orderInterfaceFactory;
        $this->authorizationInterfaceFactory = $authorizationInterfaceFactory;
        $this->sessionTokenizeInterfaceFactory = $sessionTokenizeInterfaceFactory;
        $this->checkoutSession = $checkoutSession;
        $this->apiPayloadBuilder = $apiPayloadBuilder;
        $this->sessionInterfaceFactory = $sessionInterfaceFactory;
        $this->sezzleConfig = $sezzleConfig;
        $this->sessionOrderInterfaceFactory = $sessionOrderInterfaceFactory;
        $this->amountInterfaceFactory = $amountInterfaceFactory;
        $this->tokenizeCustomerInterfaceFactory = $tokenizeCustomerInterfaceFactory;
        $this->linkInterfaceFactory = $linkInterfaceFactory;
        $this->customerInterfaceFactory = $customerInterfaceFactory;
        $this->timezone = $timezone;
    }

    /**
     * Authenticate user
     *
     * @return AuthInterface
     * @throws LocalizedException
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
        } catch (Exception $e) {
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
        $url = $this->sezzleConfig->getSezzleBaseUrl() . self::SEZZLE_CREATE_SESSION_ENDPOINT;
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
        } catch (Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway checkout error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function capture($url, $orderUUID, $amount, $isPartialCapture)
    {
        if (!$url) {
            $captureEndpoint = sprintf(self::SEZZLE_CAPTURE_BY_ORDER_UUID_ENDPOINT, $orderUUID);
            $url = $this->sezzleConfig->getSezzleBaseUrl() . $captureEndpoint;
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
            return isset($body['uuid']) && $body['uuid'] ? $body['uuid'] : "";
        } catch (Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway capture error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function refund($url, $orderUUID, $amount)
    {
        if (!$url) {
            $refundEndpoint = sprintf(self::SEZZLE_REFUND_BY_ORDER_UUID_ENDPOINT, $orderUUID);
            $url = $this->sezzleConfig->getSezzleBaseUrl() . $refundEndpoint;
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
            return isset($body['uuid']) && $body['uuid'] ? $body['uuid'] : "";
        } catch (Exception $e) {
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
            $url = $this->sezzleConfig->getSezzleBaseUrl() . $orderEndpoint;
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
        } catch (Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway order error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getCustomer($url, $customerUUID)
    {
        if (!$url) {
            $customerEndpoint = sprintf(self::SEZZLE_GET_CUSTOMER_ENDPOINT, $customerUUID);
            $url = $this->sezzleConfig->getSezzleBaseUrl() . $customerEndpoint;
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
            $customerModel = $this->customerInterfaceFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $customerModel,
                $body,
                CustomerInterface::class
            );
            return $customerModel;
        } catch (Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway customer error: %1', $e->getMessage())
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
            $url = $this->sezzleConfig->getSezzleBaseUrl() . $authorizeEndpoint;
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
            $authorizationModel->setApproved(isset($body['authorization']['approved']));
            return $authorizationModel;
        } catch (Exception $e) {
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
            $url = $this->sezzleConfig->getSezzleBaseUrl() . $sessionTokenEndpoint;
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
        } catch (Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway get token error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function release($url, $orderUUID, $amount)
    {
        if (!$url) {
            $releaseEndpoint = sprintf(self::SEZZLE_RELEASE_BY_ORDER_UUID_ENDPOINT, $orderUUID);
            $url = $this->sezzleConfig->getSezzleBaseUrl() . $releaseEndpoint;
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
        } catch (Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway release payment error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getSettlementSummaries($from = null, $to = null)
    {
        $url = $this->sezzleConfig->getSezzleBaseUrl() . self::SEZZLE_GET_SETTLEMENT_SUMMARIES_ENDPOINT;
        $range = $this->sezzleConfig->getSettlementReportsRange();
        $interval = sprintf("P%sD", $range);
        $currentDate = $this->timezone->date();
        $endDate = clone $currentDate;
        $startDate = $from ?: $currentDate->sub(new DateInterval($interval))->format('Y-m-d');
        $endDate = $to ?: $endDate->format('Y-m-d');
        $url = $url . "?start-date=" . $startDate . "&end-date=" . $endDate;
        $auth = $this->authenticate();
        try {
            $response = $this->apiProcessor->call(
                $url,
                $auth->getToken(),
                null,
                ZendClient::GET
            );
            return $this->jsonHelper->jsonDecode($response);
        } catch (Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway get settlement summaries error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getSettlementDetails($payoutUUID)
    {
        $settlementDetailsEndpoint = sprintf(self::SEZZLE_GET_SETTLEMENT_DETAILS_ENDPOINT, $payoutUUID);
        $url = $this->sezzleConfig->getSezzleBaseUrl() . $settlementDetailsEndpoint;
        $auth = $this->authenticate();
        try {
            return $this->apiProcessor->call(
                $url,
                $auth->getToken(),
                null,
                ZendClient::GET
            );
        } catch (Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway get settlement details error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function reauthorizeOrder($url, $orderUUID, $amount)
    {
        if (!$url) {
            $reauthEndpoint = sprintf(self::SEZZLE_REAUTHORIZE_ORDER_UUID_ENDPOINT, $orderUUID);
            $url = $this->sezzleConfig->getSezzleBaseUrl() . $reauthEndpoint;
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
                ZendClient::POST,
                true
            );
            $body = $this->jsonHelper->jsonDecode($response["body"]);
            $responseStatusCode = $response["status_code"];
            if ($responseStatusCode == 422) {
                throw new Exception(__("Tokenized customer not found."));
            }
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
            $authorizationModel->setApproved(isset($body['authorization']['approved']));
            return $authorizationModel;
        } catch (Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Capturing expired auth error : %1', $e->getMessage())
            );
        }
    }
}
