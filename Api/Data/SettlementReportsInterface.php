<?php
/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Api\Data;

/**
 * Interface SettlementReportsInterface
 * @package Sezzle\Sezzlepay\Api\Data
 */
interface SettlementReportsInterface
{

    const UUID = "uuid";
    const PAYOUT_CURRENCY = "payout_currency";
    const PAYOUT_DATE = "payout_date";
    const NET_SETTLEMENT_AMOUNT = "net_settlement_amount";
    const FOREX_FEES = "forex_fees";
    const STATUS = "status";

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
    public function getPayoutCurrency();

    /**
     * @param string $payoutCurrency
     * @return $this
     */
    public function setPayoutCurrency($payoutCurrency);

    /**
     * @return string|null
     */
    public function getPayoutDate();

    /**
     * @param string $payoutDate
     * @return $this
     */
    public function setPayoutDate($payoutDate);

    /**
     * @return int|null
     */
    public function getNetSettlementAmount();

    /**
     * @param int $netSettlementAmount
     * @return $this
     */
    public function setNetSettlementAmount($netSettlementAmount);

    /**
     * @return int|null
     */
    public function getForexFees();

    /**
     * @param int $forexFees
     * @return $this
     */
    public function setForexFees($forexFees);

    /**
     * @return string|null
     */
    public function getStatus();

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status);
}
