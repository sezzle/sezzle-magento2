<?php


namespace Sezzle\Sezzlepay\Api\Data;


/**
 * Interface AmountInterface
 * @package Sezzle\Sezzlepay\Api\Data
 */
interface AmountInterface
{
    const AMOUNT_IN_CENTS = "amount_in_cents";
    const CURRENCY = "currency";

    /**
     * @return int|null
     */
    public function getAmountInCents();

    /**
     * @param int $amountInCents
     * @return $this
     */
    public function setAmountInCents($amountInCents);

    /**
     * @return string|null
     */
    public function getCurrency();

    /**
     * @param string $currency
     * @return $this
     */
    public function setCurrency($currency);

}
