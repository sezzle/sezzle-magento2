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

    const KEY_CAPTURE_AMOUNT = 'sezzle_capture_amount';

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
        $amount = SubjectReader::readAmount($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $orderUUID = $payment->getAdditionalInformation(AuthorizationHandler::KEY_ORIGINAL_ORDER_UUID);

        $capturedAmount = $payment->getAdditionalInformation(self::KEY_CAPTURE_AMOUNT) + $amount;
        if (!$payment->getAdditionalInformation(AuthorizationHandler::KEY_AUTH_AMOUNT)) {
            $payment->setAdditionalInformation(AuthorizationHandler::KEY_AUTH_AMOUNT, $capturedAmount);
        }

        $payment->setAdditionalInformation(self::KEY_CAPTURE_AMOUNT, $capturedAmount)
            ->setAdditionalInformation($response['uuid'], $orderUUID)
            ->setAdditionalInformation('payment_type', $this->adapter->getConfigPaymentAction())
            ->setTransactionId($response['uuid'])
            ->setIsTransactionClosed(true);
    }
}
