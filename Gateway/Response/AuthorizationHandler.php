<?php

namespace Sezzle\Sezzlepay\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Model\Method\Adapter;

/**
 * AuthorizationHandler
 */
class AuthorizationHandler implements HandlerInterface {

    const KEY_ORIGINAL_ORDER_UUID = 'sezzle_original_order_uuid';
    const KEY_AUTH_AMOUNT = 'sezzle_auth_amount';

    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * @param Adapter $adapter
     */
    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }

    /**
     * AuthorizationHandler constructor
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $amount = SubjectReader::readAmount($handlingSubject);

        /** @var Payment $orderPayment */
        $payment = $paymentDO->getPayment();

        $sezzleOrderUUID = $payment->getAdditionalInformation(self::KEY_ORIGINAL_ORDER_UUID);

        $payment->setAdditionalInformation(self::KEY_AUTH_AMOUNT, $amount);
        $payment->setAdditionalInformation('payment_type', $this->adapter->getConfigPaymentAction());
        $payment->setTransactionId($sezzleOrderUUID)->setIsTransactionClosed(false);
    }
}
