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
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\Ui\ConfigProvider;

class SendOrderConfirmationMail implements ObserverInterface
{

    /**
     * @var OrderSender
     */
    private $orderSender;
    /**
     * @var Data
     */
    private $helper;

    /**
     * SendOrderConfirmationMail constructor.
     * @param OrderSender $orderSender
     * @param Data $helper
     */
    public function __construct(
        OrderSender $orderSender,
        Data        $helper
    )
    {
        $this->orderSender = $orderSender;
        $this->helper = $helper;
    }

    /**
     * @param Observer $observer
     * @return SendOrderConfirmationMail
     */
    public function execute(Observer $observer): SendOrderConfirmationMail
    {
        /* @var Order $order */
        $order = $observer->getEvent()->getData('order');
        try {
            if (!$order || $order->getPayment()->getMethod() !== ConfigProvider::CODE) {
                return $this;
            }
            $this->orderSender->send($order);
        } catch (Exception $e) {
            $this->helper->logSezzleActions(
                "Sezzle Order Confirmation Mail Sending Error: " .
                $e->getMessage()
            );
        }

        return $this;
    }
}
