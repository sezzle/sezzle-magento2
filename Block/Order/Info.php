<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Order;

use Sezzle\Sezzlepay\Model\Sezzle;

/**
 * Class Info
 */
class Info extends \Magento\Sales\Block\Order\Info
{
    /**
     * @var string
     */
    protected $_template = 'Sezzle_Sezzlepay::order/sezzle_order_reference.phtml';

    public function getSezzleOrderReferenceID()
    {
        return $this->getOrder()->getPayment()->getAdditionalInformation(Sezzle::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID);
    }

    public function isSezzleOrder()
    {
        return $this->getOrder()->getPayment()->getMethod();
    }
}
