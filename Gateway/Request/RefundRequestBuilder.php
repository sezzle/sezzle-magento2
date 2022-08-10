<?php

namespace Sezzle\Sezzlepay\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Helper\Util;

/**
 * RefundRequestBuilder
 */
class RefundRequestBuilder implements BuilderInterface
{

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param Data $helper
     */
    public function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $amount = SubjectReader::readAmount($buildSubject);

        $this->helper->logSezzleActions([
            'log_origin' => __METHOD__,
            'amount' => $amount
        ]);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $txnUUID = $payment->getCreditMemo()->getInvoice()->getTransactionId();
        $orderUUID = $payment->getAdditionalInformation($txnUUID);


        return [
            '__store_id' => $payment->getOrder()->getStoreId(),
            '__route_params' => [
                'order_uuid' => $orderUUID
            ],
            'amount_in_cents' => Util::formatToCents($amount),
            'currency' => $payment->getOrder()->getBaseCurrencyCode()
        ];
    }
}
