<?php

namespace Sezzle\Sezzlepay\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Sezzle\Sezzlepay\Gateway\Response\AuthorizationHandler;
use Sezzle\Sezzlepay\Helper\Util;

/*
 * CaptureRequestBuilder
 */
class CaptureRequestBuilder implements BuilderInterface
{

    const GROUP = "capture_amount";
    const AMOUNT_IN_CENTS = "amount_in_cents";
    const CURRENCY = "currency";

    const ROUTE_PARAMS = "route_params";

    const ORDER_UUID = "order_uuid";
    const __STORE_ID = "__storeId";


    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        $amount = SubjectReader::readAmount($buildSubject);

        return [
            self::__STORE_ID => $payment->getOrder()->getStoreId(),
            self::ROUTE_PARAMS => [
                self::ORDER_UUID => $payment->getAdditionalInformation(AuthorizationHandler::KEY_ORIGINAL_ORDER_UUID)
            ],
            self::GROUP => [
                self::AMOUNT_IN_CENTS => Util::formatToCents($amount),
                self::CURRENCY => $paymentDO->getOrder()->getBaseCurrencyCode()
            ]
        ];
    }
}
