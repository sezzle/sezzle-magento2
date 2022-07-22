<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Order;

use Sezzle\Sezzlepay\Gateway\Request\CustomerOrderRequestBuilder;
use Sezzle\Sezzlepay\Model\Ui\ConfigProvider;

/**
 * Class Info
 */
class Info extends \Magento\Sales\Block\Order\Info
{
    /**
     * @var string
     */
    protected $_template = 'Sezzle_Sezzlepay::order/sezzle_order_reference.phtml';

    /**
     * Get Sezzle Order Reference ID
     *
     * @return string[]
     */
    public function getSezzleOrderReferenceID()
    {
        return $this->getOrder()->getPayment()->getAdditionalInformation(CustomerOrderRequestBuilder::KEY_REFERENCE_ID);
    }

    /**
     * Check if current order is Sezzle Order
     *
     * @return bool
     */
    public function isSezzleOrder()
    {
        return $this->getOrder()->getPayment()->getMethod() == ConfigProvider::CODE;
    }
}
