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
use Sezzle\Sezzlepay\Helper\Data;

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
     * @var Data
     */
    private $helper;

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
     * @param Data $helper
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
        Data                        $helper,
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
        $this->helper = $helper;
        $this->reauthOrderCommand = $reauthOrderCommand;
        $this->authValidator = $authValidator;
    }

    /**
     * @inheritDoc
     * @throws CommandException
     * @throws LocalizedException
     */
    public function execute(array $commandSubject): void
    {
        $amount = SubjectReader::readAmount($commandSubject);
        if ($amount <= 0) {
            throw new CommandException(__('Invalid amount for capture.'));
        }

        $authValidatorResult = $this->authValidator->validate($commandSubject);
        $authValid = $authValidatorResult->isValid();

        $this->helper->logSezzleActions([
            'capture' => [
                'auth_valid' => $authValid,
                'amount' => $amount
            ]
        ]);

        // reauthorize if auth is expired
        if (!$authValid) {
            try {
                $this->reauthOrderCommand->execute($commandSubject);
            } catch (CommandException $e) {
                throw new LocalizedException(__('Reauthorization failed at Sezzle.'));
            }
        }

        parent::execute($commandSubject);
    }

}
