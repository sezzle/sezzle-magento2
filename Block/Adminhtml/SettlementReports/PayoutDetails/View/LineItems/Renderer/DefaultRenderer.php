<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails\View\LineItems\Renderer;

use Magento\Backend\Block\Template;

/**
 * Class DefaultRenderer
 * @package Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails\View\LineItems\Renderer
 */
class DefaultRenderer extends Template
{

    /**
     * Set order item
     *
     * @param array $lineItem
     * @return $this
     */
    public function setLineItem($lineItem)
    {
        $this->setData('line_item', $lineItem);
        return $this;
    }

    /**
     * Get order item
     *
     * @return array
     */
    public function getLineItem()
    {
        return $this->_getData('line_item');
    }

    /**
     * Get columns data.
     *
     * @return array
     */
    public function getColumns()
    {
        return array_key_exists('columns', $this->_data) ? $this->_data['columns'] : [];
    }
}
