<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Plugin\Sales\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Sezzle\Sezzlepay\Model\Sezzle;

class OrderPlugin
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Manipulating void action
     *
     * @param Order $subject
     * @param $result
     * @return bool
     */
    public function afterCanVoidPayment(
        Order $subject,
        $result
    ) {
        $orderId = $subject->getId();
        if (!$orderId) {
            return $result;
        }
        $order = $this->orderRepository->get($orderId);
        $sezzleOrderType = $order->getPayment()->getAdditionalInformation(Sezzle::SEZZLE_ORDER_TYPE);
        if ($sezzleOrderType != Sezzle::API_V2) {
            return false;
        } elseif ($order->hasInvoices()) {
            return false;
        }
        return $result;
    }
}
