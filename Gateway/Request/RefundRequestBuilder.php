<?php

namespace Sezzle\Sezzlepay\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Sezzle\Sezzlepay\Gateway\Response\AuthorizationHandler;
use Sezzle\Sezzlepay\Helper\Util;

/*
 * RefundRequestBuilder
 */

class RefundRequestBuilder implements BuilderInterface
{

    const AMOUNT_IN_CENTS = 'amount_in_cents';
    const CURRENCY = 'currency';

    const ROUTE_PARAMS = 'route_params';

    const ORDER_UUID = 'order_uuid';
    const __STORE_ID = '__storeId';


    /**
     * @inerhitDoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $amount = SubjectReader::readAmount($buildSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

//        if (!$txnUUID = $payment->getCreditMemo()->getInvoice()->getTransactionId()) {
//            throw new LocalizedException(__('Failed to refund the payment. Parent Transaction ID is missing.'));
//        } elseif (!$sezzleOrderUUID = $payment->getAdditionalInformation($txnUUID)) {
//            throw new LocalizedException(__('Failed to refund the payment. Order UUID is missing.'));
//        }


        $txnUUID = $payment->getCreditMemo()->getInvoice()->getTransactionId();
        $orderUUID = $payment->getAdditionalInformation($txnUUID);


        return [
            self::__STORE_ID => $payment->getOrder()->getStoreId(),
            self::ROUTE_PARAMS => [
                self::ORDER_UUID => $orderUUID
            ],
            self::AMOUNT_IN_CENTS => Util::formatToCents($amount),
            self::CURRENCY => $payment->getOrder()->getBaseCurrencyCode()
        ];
    }
}
