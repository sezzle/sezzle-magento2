<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Controller\Payment;

use Magento\Framework\Exception\LocalizedException;
use Sezzle\Sezzlepay\Controller\AbstractController\Sezzle;

/**
 * Class Cancel
 * @package Sezzle\Sezzlepay\Controller\Payment
 */
class Cancel extends Sezzle
{
    /**
     * Restore the quote if any
     * @throws LocalizedException
     */
    public function execute()
    {
        $order = $this->getOrder();
        $order->registerCancellation("Returned from Sezzle Checkout without completing payment.");
        $this->helper->logSezzleActions(
            "Returned from Sezzle Checkout without completing payment. Order not created."
        );
        return $this->resultRedirectFactory->create()->setPath('checkout');
    }
}
