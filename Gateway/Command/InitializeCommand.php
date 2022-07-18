<?php

namespace Sezzle\Sezzlepay\Gateway\Command;

use InvalidArgumentException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Model\Order;

/**
 * InitializeCommand
 */
class InitializeCommand implements CommandInterface
{

    /**
     * @inerhitDoc
     */
    public function execute(array $commandSubject): void
    {
        if (!isset($commandSubject['paymentAction']) || !is_string($commandSubject['paymentAction'])) {
            throw new InvalidArgumentException('Payment action does not exist');
        }
        $paymentAction = $commandSubject['paymentAction'];
        $stateObject = SubjectReader::readStateObject($commandSubject);
        $paymentDO = SubjectReader::readPayment($commandSubject);

        /** @var Order\Payment $payment */
        $payment = $paymentDO->getPayment();

        $order = $payment->getOrder();

        switch ($paymentAction) {
            case MethodInterface::ACTION_AUTHORIZE:
                $order->setCanSendNewEmailFlag(false);
                $payment->authorize(true, $order->getBaseTotalDue()); // base amount will be set inside
                $payment->setAmountAuthorized($order->getTotalDue());
                $order->setCustomerNote(__('Payment authorized by Sezzle.'));
                $this->updateStateObject(
                    $stateObject,
                    Order::STATE_NEW,
                    $order->getConfig()->getStateDefaultStatus(Order::STATE_NEW)
                );
                break;
            case MethodInterface::ACTION_AUTHORIZE_CAPTURE:
                $order->setCanSendNewEmailFlag(false);
                $payment->capture();
                $order->setCustomerNote(__('Payment captured by Sezzle.'));
                $this->updateStateObject(
                    $stateObject,
                    Order::STATE_PROCESSING,
                    $order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING)
                );
                break;
        }
    }

    /**
     * Updates the state object
     *
     * @param object $stateObject
     * @param string $orderState
     * @param string $orderStatus
     * @return void
     */
    private function updateStateObject(object $stateObject, string $orderState, string $orderStatus): void
    {
        $stateObject->setState($orderState);
        $stateObject->setStatus($orderStatus);
        $stateObject->setIsNotified(true);
    }
}
