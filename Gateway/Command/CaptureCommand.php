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
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Psr\Log\LoggerInterface;

/**
 * AuthorizeCommand
 */
class CaptureCommand extends GatewayCommand
{

    const KEY_ORIGINAL_ORDER_UUID = 'sezzle_original_order_uuid';
    const KEY_AUTH_AMOUNT = 'sezzle_auth_amount';

    /**
     * @var CommandInterface
     */
    private $reauthOrderCommand;

    /**
     * @var ValidatorInterface
     */
    private $authValidator;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param CommandInterface $reauthOrderCommand
     * @param ValidatorInterface $authValidator
     * @param Config $config
     * @param BuilderInterface $requestBuilder
     * @param TransferFactoryInterface $transferFactory
     * @param ClientInterface $client
     * @param LoggerInterface $logger
     * @param HandlerInterface|null $handler
     * @param ValidatorInterface|null $validator
     * @param ErrorMessageMapperInterface|null $errorMessageMapper
     */
    public function __construct(
        CommandInterface            $reauthOrderCommand,
        ValidatorInterface          $authValidator,
        Config                      $config,
        BuilderInterface            $requestBuilder,
        TransferFactoryInterface    $transferFactory,
        ClientInterface             $client,
        LoggerInterface             $logger,
        HandlerInterface            $handler = null,
        ValidatorInterface          $validator = null,
        ErrorMessageMapperInterface $errorMessageMapper = null
    )
    {
        parent::__construct($requestBuilder, $transferFactory, $client, $logger, $handler, $validator, $errorMessageMapper);
        $this->reauthOrderCommand = $reauthOrderCommand;
        $this->authValidator = $authValidator;
        $this->config = $config;
    }

    /**
     * @param array $commandSubject
     * @return void
     * @throws CommandException
     * @throws LocalizedException
     */
    public function execute(array $commandSubject): void
    {
        $paymentDO = SubjectReader::readPayment($commandSubject);

        $authValidatorResult = $this->authValidator->validate($commandSubject);

        if (!$authValidatorResult->isValid()) {
            if (!$this->config->isTokenizationEnabled($paymentDO->getPayment()->getOrder()->getStoreId())) {
                throw new LocalizedException(__('Invoice operation is not permitted. Requires a tokenized customer.'));
            }

            try {
                $this->reauthOrderCommand->execute($commandSubject);
            } catch (CommandException $e) {
                throw new LocalizedException(__('Reauthorization failed at Sezzle.'));
            }
        }

        parent::execute($commandSubject);
    }

}
