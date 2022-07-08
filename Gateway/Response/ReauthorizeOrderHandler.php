<?php

namespace Sezzle\Sezzlepay\Gateway\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;

/**
 * ReauthorizeOrderHandler
 */
class ReauthorizeOrderHandler implements HandlerInterface
{

    const KEY_EXTENDED_ORDER_UUID = 'sezzle_extended_order_uuid';

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $response = SubjectReader::readResponse($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $payment->setAdditionalInformation(self::KEY_EXTENDED_ORDER_UUID, $response['uuid']);
    }
}
