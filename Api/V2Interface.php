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
     * @return SessionInterface
     * @throws LocalizedException
     */
    public function createSession($reference);

    /**
     * Capture payment by Order UUID
     *
     * @param string $url
     * @param string $orderUUID
     * @param int $amount
     * @param bool $isPartialCapture
     * @return bool
     * @throws LocalizedException
     */
    public function capture($url, $orderUUID, $amount, $isPartialCapture);

    /**
     * Refund payment by Order uuid
     *
     * @param string $url
     * @param string $orderUUID
     * @param int $amount
     * @return bool
     * @throws LocalizedException
     */
    public function refund($url, $orderUUID, $amount);

    /**
     * Get Order by Order UUID
     *
     * @param string $url
     * @param string $orderUUID
     * @return OrderInterface
     * @throws LocalizedException
     */
    public function getOrder($url, $orderUUID);

    /**
     * Get Customer by Customer UUID
     *
     * @param string $url
     * @param string $customerUUID
     * @return CustomerInterface
     * @throws LocalizedException
     */
    public function getCustomer($url, $customerUUID);

    /**
     * Authorize Payment by Customer UUID
     *
     * @param string $url
     * @param string $customerUUID
     * @param int $amount
     * @return AuthorizationInterface
     * @throws LocalizedException
     */
    public function createOrderByCustomerUUID($url, $customerUUID, $amount);

    /**
     * Get Customer UUID by Session token
     *
     * @param string $url
     * @param string $token
     * @return TokenizeCustomerInterface
     * @throws LocalizedException
     */
    public function getTokenDetails($url, $token);

    /**
     * Release payment by Order UUID
     *
     * @param string $url
     * @param string $orderUUID
     * @param int $amount
     * @return bool
     */
    public function release($url, $orderUUID, $amount);

    /**
     * Get Settlement Report Summaries
     *
     * @param string|null $from
     * @param string|null $to
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function getSettlementSummaries($from = null, $to = null);

    /**
     * Get Settlement Report Details for a payout UUID
     *
     * @param string $payoutUUID
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getSettlementDetails($payoutUUID);

    /**
     * Reauthorize Payment by Order UUID
     *
     * @param string $url
     * @param string $orderUUID
     * @param int $amount
     * @return AuthorizationInterface
     * @throws LocalizedException
     */
    public function reauthorizeOrder($url, $orderUUID, $amount);
}
