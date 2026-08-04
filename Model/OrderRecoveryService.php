<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model;

use Magento\Framework\Exception\AlreadyExistsException;
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
     * Message Magento attaches to a reserved-increment-ID collision.
     *
     * Matched as text because the exception object itself is not always reachable - see
     * isIncrementIdCollision().
     */
    private const COLLISION_MESSAGE = 'Unique constraint violation found';

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

        $order = $this->loadOrderByIncrementId((string)$reservedId, $quote->getStoreId());
        if ($order !== null && $order->getId() && (int)$order->getQuoteId() === (int)$quote->getId()) {
            return $order;
        }

        return null;
    }

    /**
     * Load an order by increment ID, scoped to the quote's store.
     *
     * sales_order's unique key is (increment_id, store_id) - not increment_id alone - and
     * Magento's sequence only gives a store its own numbering if that store was created with a
     * distinct prefix. Stores restored from a database copy or created by direct insert share
     * the empty prefix, so several stores legitimately hold the same increment ID. The unscoped
     * Order::loadByIncrementId() then returns whichever row the database happens to yield
     * first, which may belong to another store; the quote_id check in getExistingOrder() fails,
     * the caller concludes no order exists, and it resubmits into a collision.
     *
     * @param string $incrementId
     * @param int|string|null $storeId
     * @return Order|null
     */
    private function loadOrderByIncrementId(string $incrementId, $storeId): ?Order
    {
        $order = $this->orderFactory->create();

        // A quote always carries a store in practice. If one somehow does not, fall back to the
        // unscoped lookup rather than filtering on a null store and matching nothing.
        $loaded = ($storeId === null || $storeId === '')
            ? $order->loadByIncrementId($incrementId)
            : $order->loadByIncrementIdAndStoreId($incrementId, $storeId);

        return $loaded instanceof Order ? $loaded : null;
    }

    /**
     * Whether a reserved-increment-ID collision is implicated anywhere in this failure.
     *
     * Magento does not reliably surface the collision as the top-level exception. When anything
     * throws while submitQuote() is rolling back a failed submit, Magento replaces it with a
     * plain \Exception reading "An exception occurred on
     * 'sales_model_service_quote_submit_failure' event: <message>" - and that wrapper keeps the
     * *original* exception as its previous while discarding the one raised during rollback. So
     * an AlreadyExistsException may sit in the previous chain, or may survive only as text in a
     * message. Both are checked, since matching on the top-level class alone misses it either
     * way.
     *
     * This is a hint rather than a guarantee - the text check does not survive translation - but
     * a miss is safe: the caller falls through to releasing the authorization instead of
     * retrying.
     *
     * @param \Throwable $e
     * @return bool
     */
    public function isIncrementIdCollision(\Throwable $e): bool
    {
        foreach ($this->throwableChain($e) as $link) {
            if ($link instanceof AlreadyExistsException
                || str_contains($link->getMessage(), self::COLLISION_MESSAGE)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Flatten an exception and everything it wraps into a loggable structure.
     *
     * The message that reaches the shopper - and the Magento error report - is often only the
     * outermost wrapper. The failure that actually broke order placement is further down the
     * previous chain, where nothing currently records it.
     *
     * @param \Throwable $e
     * @return array
     */
    public function describeThrowable(\Throwable $e): array
    {
        $chain = [];
        foreach ($this->throwableChain($e) as $depth => $link) {
            $chain[] = [
                'depth' => $depth,
                'class' => get_class($link),
                'message' => $link->getMessage(),
                'origin' => $link->getFile() . ':' . $link->getLine()
            ];
        }

        return $chain;
    }

    /**
     * An exception and each exception it wraps, outermost first.
     *
     * @param \Throwable $e
     * @return \Throwable[]
     */
    private function throwableChain(\Throwable $e): array
    {
        $chain = [];
        $seen = [];
        for ($link = $e; $link !== null; $link = $link->getPrevious()) {
            // Defensive: a self-referential previous chain would otherwise loop forever.
            $id = spl_object_id($link);
            if (isset($seen[$id])) {
                break;
            }
            $seen[$id] = true;
            $chain[] = $link;
        }

        return $chain;
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
