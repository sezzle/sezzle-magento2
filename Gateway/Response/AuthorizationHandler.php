<?php

namespace Sezzle\Sezzlepay\Gateway\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Model\Method\Adapter;

/**
 * AuthorizationHandler
 */
class AuthorizationHandler implements HandlerInterface
{

    const KEY_ORIGINAL_ORDER_UUID = 'sezzle_original_order_uuid';
    const KEY_AUTH_AMOUNT = 'sezzle_auth_amount';

    /**
     * @var Adapter
     */
    private $adapter;

    /**
     * @param Adapter $adapter
     */
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * AuthorizationHandler constructor
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

        $sezzleOrderUUID = $payment->getAdditionalInformation(self::KEY_ORIGINAL_ORDER_UUID);

        $payment->setAdditionalInformation(self::KEY_AUTH_AMOUNT, $amount)
            ->setAdditionalInformation('payment_type', $this->adapter->getConfigPaymentAction())
            ->setTransactionId($sezzleOrderUUID)->setIsTransactionClosed(false);
    }
}
