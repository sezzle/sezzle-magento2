<?php

namespace Sezzle\Sezzlepay\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Sezzle\Sezzlepay\Gateway\Response\AuthorizationHandler;
use Sezzle\Sezzlepay\Gateway\Response\ReauthorizeOrderHandler;
use Sezzle\Sezzlepay\Helper\Util;


/**
 * CaptureRequestBuilder
 */
class CaptureRequestBuilder implements BuilderInterface
{

    const CAPTURE_AMOUNT = 'capture_amount';
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

        $orderUUID = $payment->getAdditionalInformation(ReauthorizeOrderHandler::KEY_EXTENDED_ORDER_UUID)
            ?: $payment->getAdditionalInformation(AuthorizationHandler::KEY_ORIGINAL_ORDER_UUID);

        return [
            self::__STORE_ID => $payment->getOrder()->getStoreId(),
            self::ROUTE_PARAMS => [
                self::ORDER_UUID => $orderUUID
            ],
            self::CAPTURE_AMOUNT => [
                self::AMOUNT_IN_CENTS => Util::formatToCents($amount),
                self::CURRENCY => $payment->getOrder()->getBaseCurrencyCode()
            ]
        ];
    }
}
