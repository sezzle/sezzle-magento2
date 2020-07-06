<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails\View\Tab;

use Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails\PayoutDetails;

/**
 * Order history tab
 *
 * @api
 * @since 100.0.2
 */
class LineItems extends PayoutDetails implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'Sezzle_Sezzlepay::settlement_reports/payout_details/view/tab/line_items.phtml';

    public function getColumns()
    {
        $columns = array_key_exists('columns', $this->_data) ? $this->_data['columns'] : [];
        return $columns;
    }


    public function getLineItemDetails()
    {
        return $this->getPayoutDetails()[1];
    }

    public function getColumnNames()
    {
        return $this->getLineItemDetails()['header'];
    }

    public function getColumnData()
    {
        return $this->getLineItemDetails()['data'];
    }

    public function getItemRenderer()
    {
        $renderer = $this->getChildBlock('default');
        if (!$renderer instanceof \Magento\Framework\View\Element\BlockInterface) {
            throw new \RuntimeException('Renderer for type "' . 'default' . '" does not exist.');
        }
        $renderer->setColumnRenders($this->getLayout()->getGroupChildNames($this->getNameInLayout(), 'column'));

        return $renderer;
    }

    public function getLineItemHtml($item)
    {
        return $this->getItemRenderer()->setLineItem($item)->toHtml();
    }

    /**
     * @inheritdoc
     */
    public function getTabLabel()
    {
        return __('Line Items');
    }

    /**
     * @inheritdoc
     */
    public function getTabTitle()
    {
        return __('Payout Line Items');
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function isHidden()
    {
        return false;
    }
}
