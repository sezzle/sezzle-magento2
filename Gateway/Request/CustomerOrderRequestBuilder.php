<?php

namespace Sezzle\Sezzlepay\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Sezzle\Sezzlepay\Helper\Util;

/*
 * CustomerOrderRequestBuilder
 */

class CustomerOrderRequestBuilder implements BuilderInterface
{

    const GROUP = 'order_amount';
    const AMOUNT_IN_CENTS = 'amount_in_cents';
    const CURRENCY = 'currency';
    const INTENT = 'intent';
    const REFERENCE_ID = 'reference_id';

    const ROUTE_PARAMS = 'route_params';

    const CUSTOMER_UUID = 'customer_uuid';
    const __STORE_ID = '__storeId';

    const KEY_CUSTOMER_UUID = 'sezzle_customer_uuid';
    const KEY_REFERENCE_ID = 'sezzle_reference_id';


    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $amount = SubjectReader::readAmount($buildSubject);

        /** @var PaymentInterface $payment */
        $payment = $paymentDO->getPayment();

        return [
            self::__STORE_ID => $payment->getQuote()->getStoreId(),
            self::ROUTE_PARAMS => [
                self::CUSTOMER_UUID => $payment->getAdditionalInformation(self::KEY_CUSTOMER_UUID)
            ],
            self::INTENT => 'AUTH',
            self::REFERENCE_ID => $payment->getAdditionalInformation(self::KEY_REFERENCE_ID),
            self::GROUP => [
                self::AMOUNT_IN_CENTS => Util::formatToCents($amount),
                self::CURRENCY => $payment->getQuote()->getBaseCurrencyCode()
            ]
        ];
    }
}
