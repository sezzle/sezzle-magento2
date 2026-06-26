<?php

namespace Sezzle\Sezzlepay\Gateway\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;

/**
 * CaptureHandler
 */
class CaptureHandler implements HandlerInterface
{

    const KEY_CAPTURE_AMOUNT = 'sezzle_capture_amount';

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

        $orderUUID = $payment->getAdditionalInformation(ReauthorizeOrderHandler::KEY_EXTENDED_ORDER_UUID)
            ?: $payment->getAdditionalInformation(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID);

        $payment->unsAdditionalInformation(ReauthorizeOrderHandler::KEY_EXTENDED_ORDER_UUID);

        $capturedAmount = $payment->getAdditionalInformation(self::KEY_CAPTURE_AMOUNT) + $amount;
        if (!$payment->hasAdditionalInformation(AuthorizeCommand::KEY_AUTH_AMOUNT)) {
            $payment->setAdditionalInformation(AuthorizeCommand::KEY_AUTH_AMOUNT, $capturedAmount);
        }

        $payment->setAdditionalInformation(self::KEY_CAPTURE_AMOUNT, $capturedAmount)
            ->setAdditionalInformation($response['uuid'], $orderUUID)
            ->setAdditionalInformation('payment_type', $payment->getMethodInstance()->getConfigPaymentAction())
            ->setTransactionId($response['uuid'])
            ->setIsTransactionClosed(true);
    }
}
