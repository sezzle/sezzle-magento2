<?php

namespace Sezzle\Sezzlepay\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Gateway\Response\ReauthorizeOrderHandler;
use Sezzle\Sezzlepay\Helper\Util;


/**
 * CaptureRequestBuilder
 */
class CaptureRequestBuilder implements BuilderInterface
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

        $orderUUID = $payment->getAdditionalInformation(ReauthorizeOrderHandler::KEY_EXTENDED_ORDER_UUID)
            ?: $payment->getAdditionalInformation(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID);

        return [
            '__storeId' => $payment->getOrder()->getStoreId(),
            'route_params' => [
                'order_uuid' => $orderUUID
            ],
            'capture_amount' => [
                'amount_in_cents' => Util::formatToCents($amount),
                'currency' => $payment->getOrder()->getBaseCurrencyCode()
            ]
        ];
    }
}
