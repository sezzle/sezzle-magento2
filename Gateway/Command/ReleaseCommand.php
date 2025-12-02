<?php

namespace Sezzle\Sezzlepay\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\GatewayCommand;
use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Psr\Log\LoggerInterface;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Gateway\Response\ReleaseHandler;

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
     * @var LoggerInterface
     */
    private $logger;

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
     * @var ValidatorInterface|null
     */
    private $validator;

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
        $this->logger = $logger;
        $this->helper = $helper;
        $this->requestBuilder = $requestBuilder;
        $this->transferFactory = $transferFactory;
        $this->client = $client;
        $this->handler = $handler;
        $this->validator = $validator;
        $this->curl = $curl;
    }

    /**
     * @inheritDoc
     * @throws CommandException
     * @throws LocalizedException
     */
    public function execute(array $commandSubject): void
    {
        $paymentDO = SubjectReader::readPayment($commandSubject);
        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

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

        // Print response to console and log
        echo "\n========== SEZZLE RELEASE API RESPONSE ==========\n";
        echo "HTTP Status: " . $httpStatus . "\n";
        echo json_encode($response, JSON_PRETTY_PRINT);
        echo "\n================================================\n\n";

        $this->helper->logSezzleActions([
            'log_origin' => __METHOD__,
            'http_status' => $httpStatus,
            'sezzle_response' => $response
        ]);

        // Check if we should update the Magento order
        $shouldUpdateOrder = false;

        if ($httpStatus == 200 || $httpStatus == 422) {
            // Success - update order
            $shouldUpdateOrder = true;
        } elseif ($httpStatus == 422) {
            // code: "already_completed"
            $shouldUpdateOrder = true;
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Order already released at Sezzle, updating Magento order anyway'
            ]);
        }

        if (!$shouldUpdateOrder) {
            // Don't update order for other error codes (401, 500, etc.)
            $errorMessage = isset($response['message']) ? $response['message'] : 'Unknown error';
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Release failed at Sezzle with non-recoverable error, not updating Magento order',
                'http_status' => $httpStatus,
                'error' => $errorMessage
            ]);
            throw new CommandException(
                __('Unable to release at Sezzle (HTTP %1): %2', $httpStatus, $errorMessage)
            );
        }

        try {
            // Validate response
            if ($this->validator !== null) {
                $result = $this->validator->validate(
                    array_merge($commandSubject, ['response' => $response])
                );
                if (!$result->isValid()) {
                    throw new CommandException(
                        __('Release validation failed: %1', implode(', ', $result->getFailsDescription()))
                    );
                }
            }

            // Handle successful response
            if ($this->handler) {
                $this->handler->handle(
                    $commandSubject,
                    $response
                );
            }
        } catch (CommandException | LocalizedException $e) {
            // Validation or handling failed, but we should still update the order
            // since HTTP status was 200 or 422 with "already_completed"
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Release validation/handling failed, but updating Magento order based on HTTP status',
                'error' => $e->getMessage()
            ]);

            // Update the Magento order status to closed
            $baseGrandTotal = $payment->getOrder()->getBaseGrandTotal();
            $payment->setAdditionalInformation(ReleaseHandler::KEY_RELEASE_AMOUNT, $baseGrandTotal);
            $payment->getOrder()->setState(Order::STATE_CLOSED)
                ->setStatus($payment->getOrder()->getConfig()->getStateDefaultStatus(Order::STATE_CLOSED));

            // Don't throw the exception, allow the operation to complete
            return;
        }
    }

}
