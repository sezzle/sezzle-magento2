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

    public const UUID = "uuid";
    public const PAYOUT_CURRENCY = "payout_currency";
    public const PAYOUT_DATE = "payout_date";
    public const NET_SETTLEMENT_AMOUNT = "net_settlement_amount";
    public const FOREX_FEES = "forex_fees";
    public const STATUS = "status";

    /**
     * @return string|null
     */
    public function getUuid(): ?string;

    /**
     * @param string $uuid
     * @return $this
     */
    public function setUuid(string $uuid): self;

    /**
     * @return string|null
     */
    public function getPayoutCurrency(): ?string;

    /**
     * @param string $payoutCurrency
     * @return $this
     */
    public function setPayoutCurrency(string $payoutCurrency): self;

    /**
     * @return string|null
     */
    public function getPayoutDate(): ?string;

    /**
     * @param string $payoutDate
     * @return $this
     */
    public function setPayoutDate(string $payoutDate): self;

    /**
     * @return int|null
     */
    public function getNetSettlementAmount(): ?int;

    /**
     * @param int $netSettlementAmount
     * @return $this
     */
    public function setNetSettlementAmount(int $netSettlementAmount): self;

    /**
     * @return int|null
     */
    public function getForexFees(): ?int;

    /**
     * @param int $forexFees
     * @return $this
     */
    public function setForexFees(int $forexFees): self;

    /**
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;
}
