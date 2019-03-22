<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Observer;

use Magento\Sales\Model\Order;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\Message\ManagerInterface;

/**
 * Order Status Track Observer
 */
class OrderStatusTrackObserver implements ObserverInterface
{

    /**
     * @var DateTime
     */
    private $dateTime;
    /**
     * @var ManagerInterface
     */
    private $messageManager;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * OrderStatusTrackObserver constructor.
     * @param DateTime $dateTime
     * @param Logger $logger
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        DateTime $dateTime,
        Logger $logger,
        ManagerInterface $messageManager
    )
    {
        $this->dateTime = $dateTime;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
    }

    /**
     * Get current timestamp when order is completed
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Order $order */
        try {
            $order = $observer->getEvent()->getOrder();
            $orderState = $order->getState();
            $orderStatus = $order->getStatus();
            $paymentCode = $order->getPayment()->getMethodInstance()->getCode();
            $isShipped = $order->hasShipments();
            $isInvoiced = $order->hasInvoices();
            $completeDefaultStatusName = $order->getConfig()->getStateDefaultStatus(Order::STATE_COMPLETE);
            if ($paymentCode == 'sezzlepay'
                && $orderState == Order::STATE_COMPLETE
                && $orderStatus == $completeDefaultStatusName
                && $isShipped && $isInvoiced) {
                $currentTime = $this->dateTime->gmtDate();
            }
        } catch (\Exception $e) {
            $this->logger->debug("Order Status Track Exception: " . $e->getMessage());
            $this->messageManager->addError(
                'Error while connecting Sezzle Gateway.'
            );
        }

    }
}
