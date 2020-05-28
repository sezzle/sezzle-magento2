<?php


namespace Sezzle\Sezzlepay\Api\Data;


/**
 * Interface PaymentActionInterface
 * @package Sezzle\Sezzlepay\Api\Data
 */
interface PaymentActionInterface
{
    const UUID = "uuid";
    const AMOUNT = "amount";

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
     * @return AmountInterface|null
     */
    public function getAmount();

    /**
     * @param AmountInterface $amount
     * @return $this
     */
    public function setAmount(AmountInterface $amount);

}
