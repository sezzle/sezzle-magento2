<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Onepage;

use Sezzle\Sezzlepay\Model\Sezzle;

class Success extends \Magento\Checkout\Block\Onepage\Success
{

    /**
     * Check if the last real order is Sezzle order
     * @return bool
     */
    public function isSezzleOrder()
    {
        $order = $this->_checkoutSession->getLastRealOrder();
        if (!$order->getId()) {
            return false;
        }
        return $order->getPayment()->getMethod() === Sezzle::PAYMENT_CODE;
    }
}
