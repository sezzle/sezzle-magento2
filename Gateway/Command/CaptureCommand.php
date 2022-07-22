<?php

namespace Sezzle\Sezzlepay\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\GatewayCommand;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;
use Magento\Payment\Model\Method\Logger as PaymentLogger;

/**
 * AuthorizeCommand
 */
class CaptureCommand extends GatewayCommand
{

    /**
     * @var CommandInterface
     */
    private $reauthOrderCommand;

    /**
     * @var ValidatorInterface
     */
    private $authValidator;

    /**
     * @var PaymentLogger
     */
    private $paymentLogger;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CommandInterface $reauthOrderCommand
     * @param ValidatorInterface $authValidator
     * @param BuilderInterface $requestBuilder
     * @param TransferFactoryInterface $transferFactory
     * @param ClientInterface $client
     * @param LoggerInterface $logger
     * @param PaymentLogger $paymentLogger
     * @param HandlerInterface|null $handler
     * @param ValidatorInterface|null $validator
     * @param ErrorMessageMapperInterface|null $errorMessageMapper
     */
    public function __construct(
        CommandInterface            $reauthOrderCommand,
        ValidatorInterface          $authValidator,
        BuilderInterface            $requestBuilder,
        TransferFactoryInterface    $transferFactory,
        ClientInterface             $client,
        LoggerInterface             $logger,
        PaymentLogger               $paymentLogger,
        HandlerInterface            $handler = null,
        ValidatorInterface          $validator = null,
        ErrorMessageMapperInterface $errorMessageMapper = null
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
        $this->paymentLogger = $paymentLogger;
        $this->reauthOrderCommand = $reauthOrderCommand;
        $this->authValidator = $authValidator;
    }

    /**
     * @inerhitDoc
     * @throws CommandException
     * @throws LocalizedException
     */
    public function execute(array $commandSubject): void
    {
        $amount = SubjectReader::readAmount($commandSubject);
        if ($amount <= 0) {
            throw new CommandException(__('Invalid amount for capture.'));
        }

        $log = [
            'capture' => [
                'auth_valid' => false,
                'amount' => $amount
            ]
        ];

        $authValidatorResult = $this->authValidator->validate($commandSubject);

        // reauthorize if auth is expired
        if (!$authValidatorResult->isValid()) {
            try {
                $this->reauthOrderCommand->execute($commandSubject);
            } catch (CommandException $e) {
                $this->logger->critical($e->getMessage());
                $log['error'] = $e->getMessage();
                throw new LocalizedException(__('Reauthorization failed at Sezzle.'));
            } finally {
                $this->paymentLogger->debug($log);
            }
        }

        $log['capture']['auth_valid'] = true;
        $this->paymentLogger->debug($log);

        parent::execute($commandSubject);
    }

}
