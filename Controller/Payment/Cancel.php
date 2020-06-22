<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Controller\Payment;

use Sezzle\Payment\Controller\AbstractController\Sezzle;

/**
 * Class Cancel
 * @package Sezzle\Payment\Controller\Payment
 */
class Cancel extends Sezzle
{
    /**
     * Restore the quote if any
     */
    public function execute()
    {
        $order = $this->getOrder();
        $order->registerCancellation("Returned from Sezzle Checkout without completing payment.");
        $this->sezzleHelper->logSezzleActions(
            "Returned from Sezzle Checkout without completing payment. Order not created."
        );
        $this->getResponse()->setRedirect(
            $this->_url->getUrl('checkout')
        );
    }
}
