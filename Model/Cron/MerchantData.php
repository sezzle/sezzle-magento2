<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 * @license     https://www.sezzle.com/LICENSE.txt
 */

namespace Sezzle\Sezzlepay\Model\Cron;

use Sezzle\Sezzlepay\Model\Gateway;

/**
 * Class MerchantData
 * @package Sezzle\Sezzlepay\Model\Cron
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
    )
    {
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
