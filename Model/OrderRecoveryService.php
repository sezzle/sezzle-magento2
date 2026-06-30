<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Helper\Util;

/**
 * Recovery helpers shared by the checkout completion controller and the GraphQl resolver.
 *
 * Both order-placement entry points have to cope with the same two failure modes: a quote whose
 * reserved increment ID has already been turned into an order (idempotency / collision recovery)
 * and a Sezzle authorization left stranded when no Magento order could be created.
 */
class OrderRecoveryService
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var V2Interface
     */
    private $v2;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param OrderFactory $orderFactory
     * @param V2Interface $v2
     * @param Data $helper
     */
    public function __construct(
        OrderFactory $orderFactory,
        V2Interface  $v2,
        Data         $helper
    )
    {
        $this->orderFactory = $orderFactory;
        $this->v2 = $v2;
        $this->helper = $helper;
    }

    /**
     * Return the order already placed for this quote, if one exists.
     *
     * @param CartInterface $quote
     * @return Order|null
     */
    public function getExistingOrder(CartInterface $quote): ?Order
    {
        $reservedId = $quote->getReservedOrderId();
        if (!$reservedId) {
            return null;
        }

        /** @var Order $order */
        $order = $this->orderFactory->create()->loadByIncrementId($reservedId);
        if ($order->getId() && (int)$order->getQuoteId() === (int)$quote->getId()) {
            return $order;
        }

        return null;
    }

    /**
     * Best-effort release of a Sezzle authorization left stranded when the Magento order
     * could not be created. Never interrupts the response flow.
     *
     * @param CartInterface $quote
     * @return void
     */
    public function releaseStrandedAuthorization(CartInterface $quote): void
    {
        try {
            $payment = $quote->getPayment();
            $orderUUID = $payment
                ? $payment->getAdditionalInformation(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID)
                : null;
            if (!$orderUUID) {
                return;
            }

            $this->v2->releasePayment(
                $orderUUID,
                Util::formatToCents($quote->getBaseGrandTotal()),
                (string)$quote->getBaseCurrencyCode(),
                (int)$quote->getStoreId()
            );
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Released stranded Sezzle authorization after failed order creation',
                'order_uuid' => $orderUUID
            ]);
        } catch (\Exception $e) {
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Failed to release stranded Sezzle authorization',
                'error' => $e->getMessage()
            ]);
        }
    }
}
