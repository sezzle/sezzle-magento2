<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails\View\LineItems\Renderer;

use Magento\Sales\Model\Order\Item;

/**
 * Adminhtml sales order item renderer
 *
 * @api
 * @since 100.0.2
 */
class DefaultRenderer extends \Magento\Backend\Block\Template
{

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
     * @since 100.1.0
     */
    public function getColumns()
    {
        $columns = array_key_exists('columns', $this->_data) ? $this->_data['columns'] : [];
        return $columns;
    }
}
