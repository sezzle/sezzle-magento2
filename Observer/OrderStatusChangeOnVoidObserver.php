<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Sezzle\Sezzlepay\Model\Ui\ConfigProvider;

class OrderStatusChangeOnVoidObserver implements ObserverInterface
{

    /**
     * @param Observer $observer
     * @return OrderStatusChangeOnVoidObserver
     */
    public function execute(Observer $observer): OrderStatusChangeOnVoidObserver
    {
        /* @var Payment $payment */
        $payment = $observer->getEvent()->getData('payment');
        if ($payment->getMethod() != ConfigProvider::CODE) {
            return $this;
        }
        $payment->getOrder()->setState(Order::STATE_CANCELED)
            ->setStatus($payment->getOrder()->getConfig()->getStateDefaultStatus(Order::STATE_CANCELED));

        return $this;
    }
}
