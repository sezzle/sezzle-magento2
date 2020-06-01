<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model\Cron;

use Sezzle\Payment\Model\Gateway;

/**
 * Class MerchantData
 * @package Sezzle\Payment\Model\Cron
 */
class MerchantData
{
    /**
     * @var Gateway\Transaction
     */
    protected $transaction;
    /**
     * @var Gateway\Heartbeat
     */
    protected $heartbeat;

    /**
     * MerchantData constructor.
     * @param Gateway\Transaction $transaction
     * @param Gateway\Heartbeat $heartbeat
     */
    public function __construct(
        Gateway\Transaction $transaction,
        Gateway\Heartbeat $heartbeat
    ) {
        $this->transaction = $transaction;
        $this->heartbeat = $heartbeat;
    }

    /**
     * Jobs for Sezzle handshake
     */
    public function execute()
    {
        $this->transaction->sendOrdersToSezzle();
        $this->heartbeat->send();
    }
}
