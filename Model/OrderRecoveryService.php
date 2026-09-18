<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model;

use Magento\Framework\DB\Adapter\DuplicateException;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
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
     * Driver-level duplicate-key signals.
     *
     * Unlike the message above, these are not passed through __(), so they still match on a
     * store running a translated locale. Magento maps MySQL errno 1062 onto DuplicateException
     * (Pdo\Mysql::$_exceptionMap), and the PDO exception underneath carries SQLSTATE 23000.
     */
    private const COLLISION_CODES = ['23000', '1062'];

    /**
     * How much of a chained exception message is kept for the log.
     *
     * Driver exceptions embed the failing SQL along with its bound values, which on a checkout
     * failure is shopper data - email, name, street, phone. This file is routinely emailed to
     * support, so the chain is capped rather than logged whole: the class, the origin and the
     * opening of the message are what identify the failure, and the tail is where the bound
     * row tends to sit.
     */
    private const MAX_LOGGED_MESSAGE_LENGTH = 500;

    /**
     * Payment additional-information key stamped once an authorization has been released.
     */
    public const KEY_AUTH_RELEASED_AT = 'sezzle_auth_released_at';

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
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @param OrderFactory $orderFactory
     * @param V2Interface $v2
     * @param Data $helper
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(
        OrderFactory           $orderFactory,
        V2Interface            $v2,
        Data                   $helper,
        OrderCollectionFactory $orderCollectionFactory,
        CartRepositoryInterface $cartRepository
    )
    {
        $this->orderFactory = $orderFactory;
        $this->v2 = $v2;
        $this->helper = $helper;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->cartRepository = $cartRepository;
    }

    /**
     * Return the order already placed for this quote, if one exists.
     *
     * Looks up by quote_id first. The increment ID is not a dependable handle on the order: a
     * third-party extension can rewrite it after placement - Ktpl_OrderPrefix does exactly
     * that on the store this recovery was written for, turning 002411368 into LS-002411368 in
     * an order-place-after hook - and once it has, an increment-ID lookup reports no order for
     * a quote that has one. Both of the callers' failure paths act on that answer: one
     * regenerates the reserved ID and resubmits, placing a second order against a single
     * authorization, and the other releases an authorization that a real order is relying on.
     *
     * quote_id is the per-checkout key the caller is really asking about, it is what the
     * increment-ID branch below already validates against, and nothing rewrites it. The
     * increment-ID lookup stays as a fallback for the case where the order row exists but
     * carries no quote reference.
     *
     * @param CartInterface $quote
     * @return Order|null
     */
    public function getExistingOrder(CartInterface $quote): ?Order
    {
        if ($order = $this->loadOrderByQuoteId((int)$quote->getId())) {
            return $order;
        }

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
     * Load the order placed for a quote, by the quote reference the order itself carries.
     *
     * @param int $quoteId
     * @return Order|null
     */
    private function loadOrderByQuoteId(int $quoteId): ?Order
    {
        if (!$quoteId) {
            return null;
        }

        $order = $this->orderCollectionFactory->create()
            ->addFieldToFilter('quote_id', $quoteId)
            ->setPageSize(1)
            ->getFirstItem();

        return ($order instanceof Order && $order->getId()) ? $order : null;
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
    private function loadOrderByIncrementId(string $incrementId, int|string|null $storeId): ?Order
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
     * the collision may sit in the previous chain, or may survive only as text in a message.
     * Both are checked, since matching on the top-level class alone misses it either way.
     *
     * Three signals are accepted, in decreasing order of confidence: DuplicateException (what
     * Magento's PDO adapter raises for MySQL errno 1062), a driver code of 23000 or 1062, and
     * the English collision phrase. The phrase alone is fragile - it goes through __(), so a
     * store on a translated locale never matches it - which is why the driver signals are
     * checked as well; they are not translated.
     *
     * $reservedId narrows a match to this checkout. Without it the answer is "some unique key
     * collided somewhere in this chain", which a duplicate coupon-usage row or a third-party
     * unique index satisfies just as well as an increment ID. Callers deciding whether to
     * regenerate the reserved ID and resubmit an authorized payment should pass it; callers
     * recording a diagnostic flag should not, since there a false negative is the costlier
     * error.
     *
     * A miss is safe either way: the caller falls through to looking for an already-placed
     * order and otherwise releasing the authorization.
     *
     * @param \Throwable $e
     * @param string|null $reservedId
     * @return bool
     */
    public function isIncrementIdCollision(\Throwable $e, ?string $reservedId = null): bool
    {
        $chain = $this->throwableChain($e);

        $collided = false;
        foreach ($chain as $link) {
            if ($this->isUniqueConflict($link)) {
                $collided = true;
                break;
            }
        }

        if (!$collided || $reservedId === null || $reservedId === '') {
            return $collided;
        }

        // Checked across the whole chain rather than against the link that carried the
        // conflict: the generic phrase and the "Duplicate entry '...'" text that names the
        // value are raised at different levels and rarely travel in the same message.
        foreach ($chain as $link) {
            if (str_contains($link->getMessage(), $reservedId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether this single throwable reports a duplicate-key conflict.
     *
     * @param \Throwable $link
     * @return bool
     */
    private function isUniqueConflict(\Throwable $link): bool
    {
        return $link instanceof DuplicateException
            || $link instanceof AlreadyExistsException
            || in_array((string)$link->getCode(), self::COLLISION_CODES, true)
            || str_contains($link->getMessage(), self::COLLISION_MESSAGE);
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
                'message' => $this->truncateForLog($link->getMessage()),
                'origin' => $link->getFile() . ':' . $link->getLine()
            ];
        }

        return $chain;
    }

    /**
     * Trim a message to what is useful for diagnosis without carrying the bound row with it.
     *
     * @param string $message
     * @return string
     */
    private function truncateForLog(string $message): string
    {
        if (mb_strlen($message) <= self::MAX_LOGGED_MESSAGE_LENGTH) {
            return $message;
        }

        return mb_substr($message, 0, self::MAX_LOGGED_MESSAGE_LENGTH) . '... [truncated]';
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
     * Stamps the payment once the release has gone through. A shopper who reloads the return
     * URL after a failure runs the whole flow again, and without a record of the release the
     * second pass would send a second releasePayment() for the same UUID and - if placement
     * happened to succeed that time - leave Magento holding an order whose authorization had
     * already been given back, surfacing much later as a capture failure at invoice time.
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

            if ($payment->getAdditionalInformation(self::KEY_AUTH_RELEASED_AT)) {
                $this->logQuietly([
                    'log_origin' => __METHOD__,
                    'message' => 'Sezzle authorization was already released for this quote; not releasing again',
                    'order_uuid' => $orderUUID
                ]);

                return;
            }

            $this->v2->releasePayment(
                $orderUUID,
                Util::formatToCents($quote->getBaseGrandTotal()),
                (string)$quote->getBaseCurrencyCode(),
                (int)$quote->getStoreId()
            );
            $this->stampReleased($quote, $payment);
            $this->logQuietly([
                'log_origin' => __METHOD__,
                'message' => 'Released stranded Sezzle authorization after failed order creation',
                'order_uuid' => $orderUUID
            ]);
        } catch (\Throwable $e) {
            $this->logQuietly([
                'log_origin' => __METHOD__,
                'message' => 'Failed to release stranded Sezzle authorization',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Record that the authorization for this quote has been given back.
     *
     * Separate from the release itself and swallowed on its own: the release has already
     * happened by this point, and failing to persist the note is not a reason to report the
     * release as failed.
     *
     * @param CartInterface $quote
     * @param mixed $payment
     * @return void
     */
    private function stampReleased(CartInterface $quote, $payment): void
    {
        try {
            $payment->setAdditionalInformation(self::KEY_AUTH_RELEASED_AT, time());
            $this->cartRepository->save($quote);
        } catch (\Throwable $stampFailure) {
            $this->logQuietly([
                'log_origin' => __METHOD__,
                'message' => 'Released the Sezzle authorization but could not record it on the quote',
                'error' => $stampFailure->getMessage()
            ]);
        }
    }

    /**
     * Write to the Sezzle log without ever throwing back at the caller.
     *
     * Everything this class logs runs on a failure path, and most of it from inside a catch
     * block where the authorization release either has not happened yet or has just happened
     * and needs reporting. An exception escaping a log line there is the one failure mode this
     * whole class exists to prevent, so it is contained here rather than at each call.
     *
     * @param array $data
     * @return void
     */
    private function logQuietly(array $data): void
    {
        try {
            $this->helper->logSezzleActions($data);
        } catch (\Throwable $loggingFailure) {
            $this->helper->logCriticalFailure('Could not write to the Sezzle log', $loggingFailure);
        }
    }
}
