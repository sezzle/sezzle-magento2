<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Cron;

use Sezzle\Sezzlepay\Model\Gateway;

/**
 * Class CapturePayment
 * @package Sezzle\Sezzlepay\Model\Cron
 */
class CapturePayment
{
    /**
     * @var Gateway\ProcessPayment
     */
    protected $paymentProcessor;

    /**
     * CapturePayment constructor.
     * @param Gateway\ProcessPayment $paymentProcessor
     */
    public function __construct(
        Gateway\ProcessPayment $paymentProcessor
    ) {
        $this->paymentProcessor = $paymentProcessor;
    }

    /**
     * Jobs for capturing non captured orders
     */
    public function execute()
    {
        $this->paymentProcessor->capturePayment();
    }
}
