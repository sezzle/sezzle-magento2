<?php

namespace Sezzle\Sezzlepay\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Sezzle\Sezzlepay\Gateway\Response\AuthorizationHandler;
use Sezzle\Sezzlepay\Helper\Util;

/*
 * ReauthorizeOrderRequestBuilder
 */

class ReauthorizeOrderRequestBuilder implements BuilderInterface
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

        return [
            self::__STORE_ID => $payment->getOrder()->getStoreId(),
            self::ROUTE_PARAMS => [
                self::ORDER_UUID => $payment->getAdditionalInformation(AuthorizationHandler::KEY_ORIGINAL_ORDER_UUID)
            ],
            self::AMOUNT_IN_CENTS => Util::formatToCents($amount),
            self::CURRENCY => $payment->getOrder()->getBaseCurrencyCode()
        ];
    }
}
