<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Api;

use Magento\Framework\Exception\LocalizedException;
use Sezzle\Payment\Api\Data\AuthInterface;
use Sezzle\Payment\Api\Data\AuthorizationInterface;
use Sezzle\Payment\Api\Data\CustomerInterface;
use Sezzle\Payment\Api\Data\OrderInterface;
use Sezzle\Payment\Api\Data\SessionInterface;
use Sezzle\Payment\Api\Data\TokenizeCustomerInterface;

interface V2Interface
{
    /**
     * Authenticate user
     *
     * @return AuthInterface
     * @throws LocalizedException
     */
    public function authenticate();

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
    public function captureByOrderUUID($url, $orderUUID, $amount, $isPartialCapture);

    /**
     * Refund payment by Order uuid
     *
     * @param string $url
     * @param string $orderUUID
     * @param int $amount
     * @return bool
     * @throws LocalizedException
     */
    public function refundByOrderUUID($url, $orderUUID, $amount);

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
     * @param string $orderUUID
     * @param int $amount
     * @return bool
     * @throws LocalizedException
     * @throws LocalizedException
     */
    public function releasePaymentByOrderUUID($url, $orderUUID, $amount);
}
