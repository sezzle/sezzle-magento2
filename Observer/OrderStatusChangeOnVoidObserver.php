<?php

namespace Sezzle\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Sezzle\Payment\Model\Sezzle;

class OrderStatusChangeOnVoidObserver implements ObserverInterface
{

    /**
     * @param Observer $observer
     * @return OrderStatusChangeOnVoidObserver
     */
    public function execute(Observer $observer)
    {
        /* @var Payment $payment */
        $payment = $observer->getEvent()->getData('payment');
        if ($payment->getMethod() != Sezzle::PAYMENT_CODE) {
            return $this;
        }
        $payment->getOrder()->setState(Order::STATE_CLOSED)
            ->setStatus($payment->getOrder()->getConfig()->getStateDefaultStatus(Order::STATE_CLOSED));

        return $this;
    }
}
