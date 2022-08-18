<?php

namespace Sezzle\Sezzlepay\Gateway\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;

/**
 * ReleaseHandler
 */
class ReleaseHandler implements HandlerInterface
{

    const KEY_RELEASE_AMOUNT = 'sezzle_release_amount';

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $payment->setAdditionalInformation(self::KEY_RELEASE_AMOUNT, $payment->getOrder()->getBaseGrandTotal())
            ->setTransactionId($response['uuid']);
        $payment->getOrder()->setState(Order::STATE_CLOSED)
            ->setStatus($payment->getOrder()->getConfig()->getStateDefaultStatus(Order::STATE_CLOSED));
    }
}
