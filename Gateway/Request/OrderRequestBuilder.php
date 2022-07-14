<?php

namespace Sezzle\Sezzlepay\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Sezzle\Sezzlepay\Gateway\Response\AuthorizationHandler;

/*
 * OrderRequestBuilder
 */

class OrderRequestBuilder implements BuilderInterface
{
    const ROUTE_PARAMS = 'route_params';

    const ORDER_UUID = 'order_uuid';
    const __STORE_ID = '__storeId';


    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var PaymentInterface $payment */
        $payment = $paymentDO->getPayment();

        return [
            self::__STORE_ID => $payment->getQuote()->getStoreId(),
            self::ROUTE_PARAMS => [
                self::ORDER_UUID => $payment->getAdditionalInformation(AuthorizationHandler::KEY_ORIGINAL_ORDER_UUID)
            ]
        ];
    }
}
