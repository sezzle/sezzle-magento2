<?php

namespace Sezzle\Sezzlepay\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Sezzle\Sezzlepay\Helper\Util;

/**
 * CustomerOrderRequestBuilder
 */
class CustomerOrderRequestBuilder implements BuilderInterface
{

    /**
     * Order amount
     */
    const ORDER_AMOUNT = 'order_amount';

    /**
     * Amount in cents
     */
    const AMOUNT_IN_CENTS = 'amount_in_cents';

    /**
     * Currency
     */
    const CURRENCY = 'currency';

    /**
     * Intent(AUTH or CAPTURE)
     */
    const INTENT = 'intent';

    /**
     * Reference ID
     */
    const REFERENCE_ID = 'reference_id';

    /**
     * Route params
     */
    const ROUTE_PARAMS = 'route_params';

    /**
     * Customer UUID
     */
    const CUSTOMER_UUID = "customer_uuid";

    /**
     * Store ID
     */
    const __STORE_ID = "__storeId";

    /**
     * Customer UUID
     */
    const KEY_CUSTOMER_UUID = "sezzle_customer_uuid";

    /**
     * Reference ID
     */
    const KEY_REFERENCE_ID = 'sezzle_reference_id';


    /**
     * @inerhitDoc
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
            self::ORDER_AMOUNT => [
                self::AMOUNT_IN_CENTS => Util::formatToCents($amount),
                self::CURRENCY => $payment->getQuote()->getBaseCurrencyCode()
            ]
        ];
    }
}
