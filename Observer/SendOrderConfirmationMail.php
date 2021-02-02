<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\Sezzle;

class SendOrderConfirmationMail implements ObserverInterface
{

    /**
     * @var OrderSender
     */
    private $orderSender;
    /**
     * @var Data
     */
    private $sezzleHelper;

    /**
     * SendOrderConfirmationMail constructor.
     * @param OrderSender $orderSender
     * @param Data $sezzleHelper
     */
    public function __construct(
        OrderSender $orderSender,
        Data $sezzleHelper
    ) {
        $this->orderSender = $orderSender;
        $this->sezzleHelper = $sezzleHelper;
    }

    /**
     * @param Observer $observer
     * @return SendOrderConfirmationMail
     */
    public function execute(Observer $observer)
    {
        /* @var Order $order */
        $order = $observer->getEvent()->getData('order');
        try {
            if (!$order || $order->getPayment()->getMethod() !== Sezzle::PAYMENT_CODE) {
                return $this;
            }
            $this->orderSender->send($order);
        } catch (Exception $e) {
            $this->sezzleHelper->logSezzleActions(
                "Sezzle Order Confirmation Mail Sending Error: " .
                $e->getMessage()
            );
        }

        return $this;
    }
}
