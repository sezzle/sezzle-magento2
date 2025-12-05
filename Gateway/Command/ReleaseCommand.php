<?php

namespace Sezzle\Sezzlepay\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\GatewayCommand;
use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * ReleaseCommand
 */
class ReleaseCommand extends GatewayCommand
{

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var BuilderInterface
     */
    private $requestBuilder;

    /**
     * @var TransferFactoryInterface
     */
    private $transferFactory;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var HandlerInterface|null
     */
    private $handler;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @param BuilderInterface $requestBuilder
     * @param TransferFactoryInterface $transferFactory
     * @param ClientInterface $client
     * @param LoggerInterface $logger
     * @param Data $helper
     * @param Curl $curl
     * @param HandlerInterface|null $handler
     * @param ValidatorInterface|null $validator
     * @param ErrorMessageMapperInterface|null $errorMessageMapper
     */
    public function __construct(
        BuilderInterface            $requestBuilder,
        TransferFactoryInterface    $transferFactory,
        ClientInterface             $client,
        LoggerInterface             $logger,
        Data                        $helper,
        Curl                        $curl,
        ?HandlerInterface            $handler = null,
        ?ValidatorInterface          $validator = null,
        ?ErrorMessageMapperInterface $errorMessageMapper = null
    )
    {
        parent::__construct(
            $requestBuilder,
            $transferFactory,
            $client,
            $logger,
            $handler,
            $validator,
            $errorMessageMapper
        );
        $this->helper = $helper;
        $this->requestBuilder = $requestBuilder;
        $this->transferFactory = $transferFactory;
        $this->client = $client;
        $this->handler = $handler;
        $this->curl = $curl;
    }

    /**
     * @inheritDoc
     * @throws CommandException
     * @throws LocalizedException
     */
    public function execute(array $commandSubject): void
    {
        $this->helper->logSezzleActions([
            'log_origin' => __METHOD__,
            'action' => 'release'
        ]);

        // Build request
        $request = $this->requestBuilder->build($commandSubject);
        $transferO = $this->transferFactory->create($request);

        // Make API call and capture response
        $response = $this->client->placeRequest($transferO);

        // Get HTTP status code
        $httpStatus = $this->curl->getStatus();

        // Check if we should update the Magento order
        $shouldUpdateOrder = false;

        if ($httpStatus === 200) {
            // Success - update order
            $shouldUpdateOrder = true;
        } elseif ($httpStatus === 422) {
            // if auth expired automatically or was manually released vis Sezzle dashboard, consider it successful and still update the order details in Magento
            $shouldUpdateOrder = true;
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Order already released at Sezzle, updating Magento order anyway'
            ]);
        }
        if ($shouldUpdateOrder) {
            // Handle successful response
            if ($this->handler) {
                $this->handler->handle(
                    $commandSubject,
                    $response
                );
            }
        } else {
            // Don't update order for other error codes (401, 500, etc.)
            $errorMessage = (is_array($response) && isset($response[0]['message'])) ? $response[0]['message'] : 'Unknown error';
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Release failed at Sezzle with non-recoverable error, not updating Magento order',
                'http_status' => $httpStatus,
                'sezzle_response' => $response,
                'error' => $errorMessage
            ]);
            throw new CommandException(
                __('Unable to release at Sezzle (HTTP %1): %2', $httpStatus, $errorMessage)
            );
        }
    }
}
