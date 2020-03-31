<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Controller\Standard;

use Sezzle\Sezzlepay\Controller\AbstractController\SezzlePay;

/**
 * Class Cancel
 * @package Sezzle\Sezzlepay\Controller\Standard
 */
class Cancel extends SezzlePay
{
    /**
     * Cancel the order
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
