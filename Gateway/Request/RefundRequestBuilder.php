<?php

namespace Sezzle\Sezzlepay\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Sezzle\Sezzlepay\Helper\Util;

/**
 * RefundRequestBuilder
 */
class RefundRequestBuilder implements BuilderInterface
{

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $amount = SubjectReader::readAmount($buildSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $txnUUID = $payment->getCreditMemo()->getInvoice()->getTransactionId();
        $orderUUID = $payment->getAdditionalInformation($txnUUID);


        return [
            '__storeId' => $payment->getOrder()->getStoreId(),
            'route_params' => [
                'order_uuid' => $orderUUID
            ],
            'amount_in_cents' => Util::formatToCents($amount),
            'currency' => $payment->getOrder()->getBaseCurrencyCode()
        ];
    }
}
