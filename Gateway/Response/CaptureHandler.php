<?php

namespace Sezzle\Sezzlepay\Gateway\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\Method\Adapter;
use Magento\Sales\Model\Order\Payment;

/**
 * CaptureHandler
 */
class CaptureHandler implements HandlerInterface
{

    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * CaptureHandler constructor
     *
     * @param Adapter $adapter
     */
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $response = SubjectReader::readResponse($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $payment->setAdditionalInformation('payment_type', $this->adapter->getConfigPaymentAction())
            ->setTransactionId($response['uuid'])
            ->setIsTransactionClosed(true);
    }
}
