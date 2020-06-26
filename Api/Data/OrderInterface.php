<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Api\Data;

/**
 * Interface OrderInterface
 * @package Sezzle\Sezzlepay\Api\Data
 */
interface OrderInterface
{
    const UUID = "uuid";
    const INTENT = "intent";
    const REFERENCE_ID = "reference_id";
    const DESCRIPTION = "description";
    const ORDER_AMOUNT = "order_amount";
    const CUSTOMER = "customer";
    const AUTHORIZATION = "authorization";

    const AMOUNT_IN_CENTS = "amount_in_cents";
    const CAPTURE_EXPIRATION = "capture_expiration";

    /**
     * @return string|null
     */
    public function getUuid();

    /**
     * @param string $uuid
     * @return $this
     */
    public function setUuid($uuid);

    /**
     * @return string|null
     */
    public function getIntent();

    /**
     * @param string $intent
     * @return $this
     */
    public function setIntent($intent);

    /**
     * @return string|null
     */
    public function getReferenceID();

    /**
     * @param string $referenceID
     * @return $this
     */
    public function setReferenceID($referenceID);

    /**
     * @return string|null
     */
    public function getDescription();

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description);

    /**
     * @return \Sezzle\Sezzlepay\Api\Data\AmountInterface|null
     */
    public function getOrderAmount();

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\AmountInterface $orderAmount
     * @return $this
     */
    public function setOrderAmount(AmountInterface $orderAmount = null);

    /**
     * @return \Sezzle\Sezzlepay\Api\Data\CustomerInterface|null
     */
    public function getCustomer();

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\CustomerInterface $customer
     * @return $this
     */
    public function setCustomer(CustomerInterface $customer = null);

    /**
     * @return \Sezzle\Sezzlepay\Api\Data\AuthorizationInterface|null
     */
    public function getAuthorization();

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\AuthorizationInterface $authorization
     * @return $this
     */
    public function setAuthorization(AuthorizationInterface $authorization = null);

    /**
     * @return string|null
     */
    public function getAmountInCents();

    /**
     * @param string $amountInCents
     * @return $this
     */
    public function setAmountInCents($amountInCents);

    /**
     * @return string|null
     */
    public function getCaptureExpiration();

    /**
     * @param string $captureExpiration
     * @return $this
     */
    public function setCaptureExpiration($captureExpiration);
}
