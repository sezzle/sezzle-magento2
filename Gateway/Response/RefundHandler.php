<?php

namespace Sezzle\Sezzlepay\Gateway\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * RefundHandler
 */
class RefundHandler implements HandlerInterface
{

    const KEY_REFUND_AMOUNT = 'sezzle_refund_amount';

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $amount = SubjectReader::readAmount($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $refundedAmount = $payment->getAdditionalInformation(self::KEY_REFUND_AMOUNT) + $amount;
        $payment->setAdditionalInformation(self::KEY_REFUND_AMOUNT, $refundedAmount);
        $payment->setTransactionId($response['uuid'])->setIsTransactionClosed(true);
    }
}
