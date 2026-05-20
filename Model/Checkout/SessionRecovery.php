<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Checkout;

use Magento\Quote\Api\Data\CartInterface;
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * Releases the Sezzle order tied to a quote payment and clears the stored
 * UUID so subsequent attempts mint a fresh session. Used when a checkout
 * round-trip leaves an orphan auth (Complete.php failed) or when the customer
 * cancels and there is no Magento order yet.
 */
class SessionRecovery
{
    /**
     * @var V2Interface
     */
    private $v2;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param V2Interface $v2
     * @param Data $helper
     */
    public function __construct(V2Interface $v2, Data $helper)
    {
        $this->v2 = $v2;
        $this->helper = $helper;
    }

    /**
     * Best-effort release of any Sezzle order UUID stored against the quote
     * payment. If release fails with a non-2xx (typically because Sezzle has
     * already captured the funds — the post-capture failure window where
     * Magento didn't save the order), fall back to refund so the customer
     * isn't charged for a checkout that never produced an order.
     *
     * Clears the UUID after the attempt so the next checkout creates a fresh
     * Sezzle order rather than re-using a now-released/refunded one.
     */
    public function release(CartInterface $quote): void
    {
        $payment = $quote->getPayment();
        if ($payment === null) {
            return;
        }

        $orderUUID = $payment->getAdditionalInformation(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID);
        if (!$orderUUID) {
            return;
        }

        $amount = (float)$quote->getBaseGrandTotal();
        $currency = (string)$quote->getBaseCurrencyCode();
        $storeId = (int)$quote->getStoreId();

        $this->helper->logSezzleActions([
            'log_origin' => __METHOD__,
            'quote_id' => $quote->getId(),
            'order_uuid' => $orderUUID
        ]);

        $releaseStatus = $this->v2->releaseOrder($orderUUID, $amount, $currency, $storeId);

        if (!$this->isSuccess($releaseStatus)) {
            // Release failed — most likely because Sezzle already captured.
            // Try refund so the customer isn't left charged for a checkout
            // that never produced a Magento order.
            $refundStatus = $this->v2->refundOrder($orderUUID, $amount, $currency, $storeId);
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'order_uuid' => $orderUUID,
                'release_status' => $releaseStatus,
                'refund_status' => $refundStatus,
                'note' => $this->isSuccess($refundStatus)
                    ? 'refunded post-capture'
                    : 'release+refund both non-2xx; manual reconciliation may be required'
            ]);
        }

        $payment->unsAdditionalInformation(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID);
    }

    private function isSuccess(int $httpStatus): bool
    {
        return $httpStatus >= 200 && $httpStatus < 300;
    }
}
