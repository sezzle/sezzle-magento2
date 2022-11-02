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
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Sezzle\Sezzlepay\Api\Data\CustomerInterface;
use Sezzle\Sezzlepay\Api\Data\CustomerInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\LinkInterface;
use Sezzle\Sezzlepay\Api\Data\LinkInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\SessionInterface;
use Sezzle\Sezzlepay\Api\Data\SessionInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\SessionOrderInterface;
use Sezzle\Sezzlepay\Api\Data\SessionOrderInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\SessionTokenizeInterface;
use Sezzle\Sezzlepay\Api\Data\SessionTokenizeInterfaceFactory;
use Sezzle\Sezzlepay\Api\Data\TokenizeCustomerInterface;
use Sezzle\Sezzlepay\Api\Data\TokenizeCustomerInterfaceFactory;
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Gateway\Http\Client;
use Sezzle\Sezzlepay\Helper\Data as SezzleHelper;
use Sezzle\Sezzlepay\Gateway\Config\Config;

/**
 * Class V2
 * @package Sezzle\Sezzlepay\Model\Api
 */
class V2 implements V2Interface
{
    const SEZZLE_CREATE_SESSION_ENDPOINT = "/session";
    const SEZZLE_GET_CUSTOMER_ENDPOINT = "/customer/%s";
    const SEZZLE_GET_SESSION_TOKEN_ENDPOINT = "/token/%s/session";
    const SEZZLE_WIDGET_QUEUE_ENDPOINT = "/widget/queue";

    const SEZZLE_GET_SETTLEMENT_SUMMARIES_ENDPOINT = "/settlements/summaries";
    const SEZZLE_GET_SETTLEMENT_DETAILS_ENDPOINT = "/settlements/details/%s";
    const SEZZLE_SEND_CONFIG_ENDPOINT = "/configuration";


    /**
     * @var Config
     */
    private $config;

    /**
     * @var SezzleHelper
     */
    private $helper;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var SessionTokenizeInterfaceFactory
     */
    private $sessionTokenizeInterfaceFactory;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var SessionInterfaceFactory
     */
    private $sessionInterfaceFactory;

    /**
     * @var SessionOrderInterfaceFactory
     */
    private $sessionOrderInterfaceFactory;

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
     * @var TransferFactoryInterface
     */
    private $transferFactory;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var BuilderInterface
     */
    private $requestBuilder;

    /**
     * V2 constructor.
     * @param DataObjectHelper $dataObjectHelper
     * @param SezzleHelper $sezzleHelper
     * @param SessionTokenizeInterfaceFactory $sessionTokenizeInterfaceFactory
     * @param CheckoutSession $checkoutSession
     * @param SessionInterfaceFactory $sessionInterfaceFactory
     * @param Config $config
     * @param SessionOrderInterfaceFactory $sessionOrderInterfaceFactory
     * @param TokenizeCustomerInterfaceFactory $tokenizeCustomerInterfaceFactory
     * @param LinkInterfaceFactory $linkInterfaceFactory
     * @param CustomerInterfaceFactory $customerInterfaceFactory
     * @param TimezoneInterface $timezone
     * @param BuilderInterface $requestBuilder
     * @param TransferFactoryInterface $transferFactory
     * @param Client $client
     */
    public function __construct(
        DataObjectHelper                 $dataObjectHelper,
        SezzleHelper                     $sezzleHelper,
        SessionTokenizeInterfaceFactory  $sessionTokenizeInterfaceFactory,
        CheckoutSession                  $checkoutSession,
        SessionInterfaceFactory          $sessionInterfaceFactory,
        Config                           $config,
        SessionOrderInterfaceFactory     $sessionOrderInterfaceFactory,
        TokenizeCustomerInterfaceFactory $tokenizeCustomerInterfaceFactory,
        LinkInterfaceFactory             $linkInterfaceFactory,
        CustomerInterfaceFactory         $customerInterfaceFactory,
        TimezoneInterface                $timezone,
        BuilderInterface                 $requestBuilder,
        TransferFactoryInterface         $transferFactory,
        Client                           $client
    )
    {
        $this->dataObjectHelper = $dataObjectHelper;
        $this->config = $config;
        $this->helper = $sezzleHelper;
        $this->sessionTokenizeInterfaceFactory = $sessionTokenizeInterfaceFactory;
        $this->checkoutSession = $checkoutSession;
        $this->sessionInterfaceFactory = $sessionInterfaceFactory;
        $this->sessionOrderInterfaceFactory = $sessionOrderInterfaceFactory;
        $this->tokenizeCustomerInterfaceFactory = $tokenizeCustomerInterfaceFactory;
        $this->linkInterfaceFactory = $linkInterfaceFactory;
        $this->customerInterfaceFactory = $customerInterfaceFactory;
        $this->timezone = $timezone;
        $this->requestBuilder = $requestBuilder;
        $this->transferFactory = $transferFactory;
        $this->client = $client;
    }

    /**
     * @inheritDoc
     */
    public function createSession(string $referenceId, int $storeId): SessionInterface
    {
        $quote = $this->checkoutSession->getQuote();
        $sessionModel = $this->sessionInterfaceFactory->create();
        try {
            $transferO = $this->transferFactory->create(array_merge([
                    '__store_id' => $storeId,
                    '__method' => Client::HTTP_POST,
                    '__uri' => $this->config->getGatewayURL($storeId) . self::SEZZLE_CREATE_SESSION_ENDPOINT
                ], $this->requestBuilder->build(['quote' => $quote, 'reference_id' => $referenceId]))
            );
            $response = $this->client->placeRequest($transferO);
            if (isset($response['order']) && ($orderObj = $response['order'])) {
                $sessionOrderModel = $this->sessionOrderInterfaceFactory->create();
                $this->dataObjectHelper->populateWithArray(
                    $sessionOrderModel,
                    $response['order'],
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
            if (isset($response['tokenize']) && ($tokenizeObj = $response['tokenize'])) {
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
            $this->helper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway checkout error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getCustomer(string $uri, string $customerUUID, int $storeId): CustomerInterface
    {
        if (!$uri) {
            $customerEndpoint = sprintf(self::SEZZLE_GET_CUSTOMER_ENDPOINT, $customerUUID);
            $uri = $this->config->getGatewayURL($storeId) . $customerEndpoint;
        }
        try {
            $transferO = $this->transferFactory->create([
                '__store_id' => $storeId,
                '__method' => Client::HTTP_GET,
                '__uri' => $uri
            ]);
            $response = $this->client->placeRequest($transferO);
            $customerModel = $this->customerInterfaceFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $customerModel,
                $response,
                CustomerInterface::class
            );
            return $customerModel;
        } catch (Exception $e) {
            $this->helper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway customer error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getTokenDetails(string $uri, string $token, int $storeId): TokenizeCustomerInterface
    {
        $sessionTokenEndpoint = sprintf(self::SEZZLE_GET_SESSION_TOKEN_ENDPOINT, $token);
        if (!$uri) {
            $uri = $this->config->getGatewayURL($storeId) . $sessionTokenEndpoint;
        }
        try {
            $transferO = $this->transferFactory->create([
                '__store_id' => $storeId,
                '__method' => Client::HTTP_GET,
                '__uri' => $uri
            ]);
            $response = $this->client->placeRequest($transferO);
            /** @var SessionTokenizeInterface $sessionTokenizeModel */
            $tokenizeCustomerModel = $this->tokenizeCustomerInterfaceFactory->create();
            if (isset($response['customer'])) {
                $this->dataObjectHelper->populateWithArray(
                    $tokenizeCustomerModel,
                    $response['customer'],
                    TokenizeCustomerInterface::class
                );
                $linksArray = [];
                if (isset($response['customer']['links']) && is_array($response['customer']['links'])) {
                    foreach ($response['customer']['links'] as $link) {
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
            $this->helper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway get token error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getSettlementSummaries(string $from = null, string $to = null): ?array
    {
        $uri = $this->config->getGatewayURL() . self::SEZZLE_GET_SETTLEMENT_SUMMARIES_ENDPOINT;
        $range = $this->config->getSettlementReportsRange();
        $interval = sprintf("P%sD", $range);
        $currentDate = $this->timezone->date();
        $endDate = clone $currentDate;
        $startDate = $from ?: $currentDate->sub(new DateInterval($interval))->format('Y-m-d');
        $endDate = $to ?: $endDate->format('Y-m-d');
        $uri = $uri . "?start-date=" . $startDate . "&end-date=" . $endDate;
        try {
            $transferO = $this->transferFactory->create([
                '__method' => Client::HTTP_GET,
                '__uri' => $uri
            ]);
            return $this->client->placeRequest($transferO);
        } catch (Exception $e) {
            $this->helper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway get settlement summaries error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getSettlementDetails(string $payoutUUID): ?string
    {
        $settlementDetailsEndpoint = sprintf(self::SEZZLE_GET_SETTLEMENT_DETAILS_ENDPOINT, $payoutUUID);
        $uri = $this->config->getGatewayURL() . $settlementDetailsEndpoint;
        try {
            $transferO = $this->transferFactory->create([
                '__method' => Client::HTTP_GET,
                '__uri' => $uri
            ]);
            return $this->client->placeRequest($transferO);
        } catch (Exception $e) {
            $this->helper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway get settlement details error: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function addToWidgetQueue(): void
    {
        $uri = $this->config->getGatewayURL() . self::SEZZLE_WIDGET_QUEUE_ENDPOINT;
        try {
            $transferO = $this->transferFactory->create([
                '__method' => Client::HTTP_POST,
                '__uri' => $uri
            ]);
            $response = $this->client->placeRequest($transferO);

            if (!empty($response)) {
                throw new Exception(__("Invalid status code: " . $response["status_code"]));
            }
        } catch (Exception $e) {
            $this->helper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Queuing widget request error : %1', $e->getMessage())
            );
        }
    }
        /**
         * @inheritDoc
         */
        public function sendConfig(string $config): void
        {
            $uri = $this->config->getGatewayURL() . self::SEZZLE_SEND_CONFIG_ENDPOINT;
            try {
                $transferO = $this->transferFactory->create([
                    '__method' => Client::HTTP_POST,
                    '__uri' => $uri
                ]);
                $response = $this->client->placeRequest($transferO);

                if (!empty($response)) {
                    throw new Exception(__("Invalid status code: " . $response["status_code"]));
                }
            } catch (Exception $e) {
                $this->helper->logSezzleActions($e->getMessage());
                throw new LocalizedException(
                    __('Gateway send config details error: %1', $e->getMessage())
                );
            }
        }

}
