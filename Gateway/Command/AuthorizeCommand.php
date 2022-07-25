<?php

namespace Sezzle\Sezzlepay\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Model\Method\Adapter;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * AuthorizeCommand
 */
class AuthorizeCommand implements CommandInterface
{

    /**
     * Sezzle Order UUID
     */
    const KEY_ORIGINAL_ORDER_UUID = 'sezzle_original_order_uuid';

    /**
     * Authorized amount
     */
    const KEY_AUTH_AMOUNT = 'sezzle_auth_amount';

    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * @var Data
     */
    private $helper;

    /**
     * AuthorizeCommand constructor.
     *
     * @param Adapter $adapter
     * @param Data $helper
     */
    public function __construct(
        Adapter $adapter,
        Data    $helper
    )
    {
        $this->adapter = $adapter;
        $this->helper = $helper;
    }

    /**
     * @inerhitDoc
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

        $this->helper->logSezzleActions(
            [
                'authorization' => [
                    'amount' => $amount,
                    'order_uuid' => $orderUUID
                ]
            ]
        );
    }
}
