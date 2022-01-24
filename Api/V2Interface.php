<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Api;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Sezzle\Sezzlepay\Api\Data\AuthorizationInterface;
use Sezzle\Sezzlepay\Api\Data\CustomerInterface;
use Sezzle\Sezzlepay\Api\Data\OrderInterface;
use Sezzle\Sezzlepay\Api\Data\SessionInterface;
use Sezzle\Sezzlepay\Api\Data\TokenizeCustomerInterface;

interface V2Interface
{
    /**
     * Create Sezzle Checkout Session
     *
     * @param string $reference
     * @param int $storeId
     * @return SessionInterface
     */
    public function createSession($reference, $storeId);

    /**
     * Capture payment by Order UUID
     *
     * @param string $url
     * @param string $orderUUID
     * @param int $amount
     * @param bool $isPartialCapture
     * @param string $currency
     * @param int $storeId
     * @return string|null
     */
    public function capture($url, $orderUUID, $amount, $isPartialCapture, $currency, $storeId);

    /**
     * Refund payment by Order uuid
     *
     * @param string $url
     * @param string $orderUUID
     * @param int $amount
     * @param string $currency
     * @param int $storeId
     * @return string|null
     */
    public function refund($url, $orderUUID, $amount, $currency, $storeId);

    /**
     * Get Order by Order UUID
     *
     * @param string $url
     * @param string $orderUUID
     * @param int $storeId
     * @return OrderInterface
     * @throws LocalizedException
     */
    public function getOrder($url, $orderUUID, $storeId);

    /**
     * Get Customer by Customer UUID
     *
     * @param string $url
     * @param string $customerUUID
     * @param int $storeId
     * @return CustomerInterface
     * @throws LocalizedException
     */
    public function getCustomer($url, $customerUUID, $storeId);

    /**
     * Authorize Payment by Customer UUID
     *
     * @param string $url
     * @param string $customerUUID
     * @param int $amount
     * @param string $currency
     * @param int $storeId
     * @return AuthorizationInterface
     */
    public function createOrderByCustomerUUID($url, $customerUUID, $amount, $currency, $storeId);

    /**
     * Get Customer UUID by Session token
     *
     * @param string $url
     * @param string $token
     * @param int $storeId
     * @return TokenizeCustomerInterface
     * @throws LocalizedException
     */
    public function getTokenDetails($url, $token, $storeId);

    /**
     * Release payment by Order UUID
     *
     * @param string $url
     * @param string $orderUUID
     * @param int $amount
     * @param string $currency
     * @param int $storeId
     * @return bool
     */
    public function release($url, $orderUUID, $amount, $currency, $storeId);

    /**
     * Get Settlement Report Summaries
     *
     * @param string|null $from
     * @param string|null $to
     * @return mixed
     * @throws LocalizedException
     */
    public function getSettlementSummaries($from = null, $to = null);

    /**
     * Get Settlement Report Details for a payout UUID
     *
     * @param string $payoutUUID
     * @return mixed
     * @throws LocalizedException
     */
    public function getSettlementDetails($payoutUUID);

    /**
     * Reauthorize Payment by Order UUID
     *
     * @param string $url
     * @param string $orderUUID
     * @param int $amount
     * @param string $currency
     * @param int $storeId
     * @return AuthorizationInterface
     */
    public function reauthorizeOrder($url, $orderUUID, $amount, $currency, $storeId);

    /**
     * Add request to widget queue
     *
     * @throws LocalizedException
     */
    public function addToWidgetQueue();
}
