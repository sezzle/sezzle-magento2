<?php

namespace Sezzle\Sezzlepay\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Helper\Util;

/**
 * ReleaseRequestBuilder
 */
class ReleaseRequestBuilder implements BuilderInterface
{

    /**
     * @inerhitDoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        return [
            '__storeId' => $payment->getOrder()->getStoreId(),
            'route_params' => [
                'order_uuid' => $payment->getAdditionalInformation(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID)
            ],
            'amount_in_cents' => Util::formatToCents($payment->getOrder()->getBaseGrandTotal()),
            'currency' => $payment->getOrder()->getBaseCurrencyCode()
        ];
    }
}
