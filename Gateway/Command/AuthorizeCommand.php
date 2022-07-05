<?php

namespace Sezzle\Sezzlepay\Gateway\Command;

use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Payment\Gateway\Command\GatewayCommand;
use Psr\Log\LoggerInterface;
use Sezzle\Sezzlepay\Gateway\Validator\OrderValidator;

class AuthorizeCommand extends GatewayCommand
{

    /**
     * @var OrderValidator
     */
    private $orderValidator;
    private $command;

    /**
     * AuthorizeCommand constructor.
     *
     * @param OrderValidator $orderValidator
     * @param BuilderInterface $requestBuilder
     * @param TransferFactoryInterface $transferFactory
     * @param ClientInterface $client
     * @param LoggerInterface $logger
     * @param HandlerInterface|null $handler
     * @param ValidatorInterface|null $validator
     */
    public function __construct(
        OrderValidator           $orderValidator,
        CommandInterface $command,
        BuilderInterface         $requestBuilder,
        TransferFactoryInterface $transferFactory,
        ClientInterface          $client,
        LoggerInterface          $logger,
        HandlerInterface         $handler = null,
        ValidatorInterface       $validator = null
    )
    {
        parent::__construct(
            $requestBuilder,
            $transferFactory,
            $client,
            $logger,
            $handler,
            $validator
        );

        $this->command = $command;
        $this->orderValidator = $orderValidator;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $commandSubject)
    {
        $this->command->execute([]);
        $result = $this->orderValidator->validate($commandSubject);
        if (!$result->isValid()) {
            throw new CommandException(
                $result->getFailsDescription()
                    ? __(implode(', ', $result->getFailsDescription()))
                    : __('Unable to validate the order.')
            );
        }

        parent::execute($commandSubject);
    }
}
