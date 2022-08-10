<?php

namespace Sezzle\Sezzlepay\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Helper\Util;

/**
 * ReleaseRequestBuilder
 */
class ReleaseRequestBuilder implements BuilderInterface
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

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        $baseGrandTotal = $payment->getOrder()->getBaseGrandTotal();

        $this->helper->logSezzleActions([
            'log_origin' => __METHOD__,
            'amount' => $baseGrandTotal
        ]);

        return [
            '__store_id' => $payment->getOrder()->getStoreId(),
            '__route_params' => [
                'order_uuid' => $payment->getAdditionalInformation(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID)
            ],
            'amount_in_cents' => Util::formatToCents($baseGrandTotal),
            'currency' => $payment->getOrder()->getBaseCurrencyCode()
        ];
    }
}
