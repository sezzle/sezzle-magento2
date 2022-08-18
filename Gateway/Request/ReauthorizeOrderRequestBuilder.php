<?php

namespace Sezzle\Sezzlepay\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Helper\Util;

/**
 * ReauthorizeOrderRequestBuilder
 */
class ReauthorizeOrderRequestBuilder implements BuilderInterface
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

        return [
            '__store_id' => $payment->getOrder()->getStoreId(),
            '__route_params' => [
                'order_uuid' => $payment->getAdditionalInformation(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID)
            ],
            'amount_in_cents' => Util::formatToCents($amount),
            'currency' => $payment->getOrder()->getBaseCurrencyCode()
        ];
    }
}
