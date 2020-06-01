<?php


namespace Sezzle\Payment\Api;

use Magento\Framework\Exception\LocalizedException;
use Sezzle\Payment\Api\Data\AuthInterface;
use Sezzle\Payment\Api\Data\AuthorizationInterface;
use Sezzle\Payment\Api\Data\OrderInterface;
use Sezzle\Payment\Api\Data\SessionInterface;

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
     * @param string $orderUUID
     * @param int $amount
     * @param bool $isPartialCapture
     * @return bool
     * @throws LocalizedException
     */
    public function captureByOrderUUID($orderUUID, $amount, $isPartialCapture);

    /**
     * Refund payment by Order uuid
     *
     * @param $orderUUID
     * @param $amount
     * @return bool
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function refundByOrderUUID($orderUUID, $amount);

    /**
     * Get Order by Order UUID
     *
     * @param string $orderUUID
     * @return OrderInterface
     * @throws LocalizedException
     */
    public function getOrder($orderUUID);

    /**
     * Authorize Payment by Customer UUID
     *
     * @param string $customerUUID
     * @param int $amount
     * @return AuthorizationInterface
     * @throws LocalizedException
     */
    public function authorizePayment($customerUUID, $amount);

    /**
     * Get Customer UUID by Session token
     *
     * @param string $token
     * @return string
     * @throws LocalizedException
     */
    public function getCustomerUUID($token);
}
