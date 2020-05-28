<?php


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

    /**
     * @return string|null
     */
    public function getUUID();

    /**
     * @param string $uuid
     * @return $this
     */
    public function setUUID($uuid);

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
     * @return AmountInterface|null
     */
    public function getOrderAmount();

    /**
     * @param AmountInterface $orderAmount
     * @return $this
     */
    public function setOrderAmount(AmountInterface $orderAmount);

    /**
     * @return mixed|null
     */
    public function getCustomer();

    /**
     * @param CustomerInterface $customer
     * @return $this
     */
    public function setCustomer(CustomerInterface $customer);

    /**
     * @return AuthorizationInterface|null
     */
    public function getAuthorization();

    /**
     * @param AuthorizationInterface $authorization
     * @return $this
     */
    public function setAuthorization(AuthorizationInterface $authorization);

}
