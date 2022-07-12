<?php

namespace Sezzle\Sezzlepay\Gateway\Command;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order;

/**
 * InitializeCommand
 */
class InitializeCommand implements CommandInterface
{

    const ACTION_AUTHORIZE = 'authorize';
    const ACTION_AUTHORIZE_CAPTURE = 'authorize_capture';

    /**
     * @param array $commandSubject
     * @throws LocalizedException
     * @return void
     */
    public function execute(array $commandSubject): void
    {
        echo 1234;
        die();
        $paymentAction = $commandSubject['paymentAction'];
        $stateObject = SubjectReader::readStateObject($commandSubject);
        $paymentDO = SubjectReader::readPayment($commandSubject);

        /** @var Order\Payment $payment */
        $payment = $paymentDO->getPayment();

        /** @var Order $order */
        $order = $paymentDO->getOrder();

        switch ($paymentAction) {
            case self::ACTION_AUTHORIZE:
                $order->setCanSendNewEmailFlag(false);
                $payment->authorize(true, $order->getBaseTotalDue()); // base amount will be set inside
                $payment->setAmountAuthorized($order->getTotalDue());
                $order->setCustomerNote(__('Payment authorized by Sezzle.'));
                $this->updateStateObject($stateObject, $order->getConfig()
                    ->getStateDefaultStatus(Order::STATE_NEW));
                break;
            case self::ACTION_AUTHORIZE_CAPTURE:
                $order->setCanSendNewEmailFlag(false);
                $payment->capture();
                $order->setCustomerNote(__('Payment captured by Sezzle.'));
                $this->updateStateObject($stateObject, $order->getConfig()
                    ->getStateDefaultStatus(Order::STATE_PROCESSING));
                break;
        }
    }

    /**
     * Updates the state object
     *
     * @param object $stateObject
     * @param string $orderStatus
     * @return void
     */
    private function updateStateObject(object $stateObject, string $orderStatus): void
    {
        $stateObject->setState(Order::STATE_PROCESSING);
        $stateObject->setStatus($orderStatus);
        $stateObject->setIsNotified(true);
    }
}
