<?php


namespace Sezzle\Payment\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Sezzle\Payment\Api\Data\AuthInterface;
use Sezzle\Payment\Api\Data\AuthorizationInterface;
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
     */
    public function captureByOrderUUID($url, $orderUUID, $amount, $isPartialCapture);

    /**
     * Refund payment by Order uuid
     *
     * @param string $url
     * @param $orderUUID
     * @param $amount
     * @return bool
     */
    public function refundByOrderUUID($url, $orderUUID, $amount);

    /**
     * Get Order by Order UUID
     *
     * @param string $url
     * @param string $orderUUID
     * @return OrderInterface
     */
    public function getOrder($url, $orderUUID);

    /**
     * Authorize Payment by Customer UUID
     *
     * @param string $url
     * @param string $customerUUID
     * @param int $amount
     * @return AuthorizationInterface
     */
    public function createOrderByCustomerUUID($url, $customerUUID, $amount);

    /**
     * Get Customer UUID by Session token
     *
     * @param string $url
     * @param string $token
     * @return TokenizeCustomerInterface
     */
    public function getTokenDetails($url, $token);

    /**
     * Release payment by Order UUID
     *
     * @param string $orderUUID
     * @param int $amount
     * @return bool
     * @throws LocalizedException
     */
    public function releasePaymentByOrderUUID($url, $orderUUID, $amount);
}
