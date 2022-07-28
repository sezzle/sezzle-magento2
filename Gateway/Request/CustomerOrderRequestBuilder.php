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
     * Customer UUID
     */
    const KEY_CUSTOMER_UUID = "sezzle_customer_uuid";

    /**
     * Reference ID
     */
    const KEY_REFERENCE_ID = 'sezzle_reference_id';


    /**
     * @inheritDoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $amount = SubjectReader::readAmount($buildSubject);

        /** @var PaymentInterface $payment */
        $payment = $paymentDO->getPayment();

        return [
            '__storeId' => $payment->getQuote()->getStoreId(),
            'route_params' => [
                'customer_uuid' => $payment->getAdditionalInformation(self::KEY_CUSTOMER_UUID)
            ],
            'intent' => 'AUTH',
            'reference_id' => $payment->getAdditionalInformation(self::KEY_REFERENCE_ID),
            'order_amount' => [
                'amount_in_cents' => Util::formatToCents($amount),
                'currency' => $payment->getQuote()->getBaseCurrencyCode()
            ]
        ];
    }
}
