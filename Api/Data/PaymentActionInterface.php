<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

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
     * @return \Sezzle\Sezzlepay\Api\Data\AmountInterface|null
     */
    public function getAmount();

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\AmountInterface $amount
     * @return $this
     */
    public function setAmount(AmountInterface $amount = null);

}
