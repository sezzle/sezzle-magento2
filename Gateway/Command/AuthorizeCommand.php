<?php

namespace Sezzle\Sezzlepay\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Model\Method\Adapter;

/**
 * AuthorizeCommand
 */
class AuthorizeCommand implements CommandInterface
{

    const KEY_ORIGINAL_ORDER_UUID = 'sezzle_original_order_uuid';
    const KEY_AUTH_AMOUNT = 'sezzle_auth_amount';

    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * AuthorizeCommand constructor.
     *
     * @param Adapter $adapter
     */
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @inheritdoc
     * @return void
     * @throws LocalizedException
     */
    public function execute(array $commandSubject): void
    {
        $amount = SubjectReader::readAmount($commandSubject);
        if ($amount <= 0) {
            throw new CommandException(__('Invalid amount for authorize.'));
        }

        $paymentDO = SubjectReader::readPayment($commandSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $orderUUID = $payment->getAdditionalInformation(self::KEY_ORIGINAL_ORDER_UUID);

        $payment->setAdditionalInformation(self::KEY_AUTH_AMOUNT, $amount)
            ->setAdditionalInformation('payment_type', $this->adapter->getConfigPaymentAction())
            ->setTransactionId($orderUUID)->setIsTransactionClosed(false);
    }
}
