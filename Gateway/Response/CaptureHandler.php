<?php

namespace Sezzle\Sezzlepay\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * CaptureHandler
 */
class CaptureHandler implements HandlerInterface
{

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $response = SubjectReader::readResponse($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $payment->setTransactionId($response["uuid"])->setIsTransactionClosed(true);
    }
}
