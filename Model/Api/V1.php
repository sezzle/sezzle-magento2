<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Api;

use Exception;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Sezzle\Sezzlepay\Api\Data\AuthInterface;
use Sezzle\Sezzlepay\Api\Data\AuthInterfaceFactory;
use Sezzle\Sezzlepay\Api\V1Interface;
use Sezzle\Sezzlepay\Gateway\Http\Client;
use Sezzle\Sezzlepay\Helper\Data as SezzleHelper;
use Sezzle\Sezzlepay\Gateway\Config\Config;

/**
 * Class V1
 * @package Sezzle\Sezzlepay\Model\Api
 */
class V1 implements V1Interface
{
    const SEZZLE_LOGGER_ENDPOINT = "/logs/%s";

    /**
     * @var Config
     */
    private $config;
    /**
     * @var SezzleHelper
     */
    private $sezzleHelper;
    /**
     * @var DateTime
     */
    private $dateTime;
    /**
     * @var TransferFactoryInterface
     */
    private $transferFactory;
    /**
     * @var Client
     */
    private $client;

    /**
     * V1 constructor.
     * @param Config $config
     * @param SezzleHelper $sezzleHelper
     * @param DateTime $dateTime
     * @param TransferFactoryInterface $transferFactory
     * @param Client $client
     */
    public function __construct(
        Config                   $config,
        SezzleHelper             $sezzleHelper,
        DateTime                 $dateTime,
        TransferFactoryInterface $transferFactory,
        Client                   $client
    )
    {
        $this->config = $config;
        $this->sezzleHelper = $sezzleHelper;
        $this->dateTime = $dateTime;
        $this->transferFactory = $transferFactory;
        $this->client = $client;
    }

    /**
     * @inheritDoc
     */
    public function sendLogsToSezzle(string $merchantUUID, string $log, int $storeId): bool
    {
        $logEndpoint = sprintf(self::SEZZLE_LOGGER_ENDPOINT, $merchantUUID);
        $uri = $this->config->getGatewayURL($storeId, Config::API_VERSION_V1) . $logEndpoint;
        $currentTime = $this->dateTime->date();
        $request = [
            'start_time' => $currentTime,
            'end_time' => $currentTime,
            'log' => $log
        ];
        try {
            $transferO = $this->transferFactory->create(array_merge([
                    '__store_id' => $storeId,
                    '__method' => Client::HTTP_POST,
                    '__uri' => $uri
                ], $request)
            );
            $response = $this->client->placeRequest($transferO);
            return isset($response['message']) && $response['message'] === 'File uploaded successfully';
        } catch (Exception $e) {
            $this->sezzleHelper->logSezzleActions($e->getMessage());
            throw new LocalizedException(
                __('Gateway log error: %1', $e->getMessage())
            );
        }
    }
}
