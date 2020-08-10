<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails\View\Tab;

use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Exception\LocalizedException;
use Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails\PayoutDetails;

/**
 * Class LineItems
 * @package Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails\View\Tab
 */
class LineItems extends PayoutDetails implements
    TabInterface
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'Sezzle_Sezzlepay::settlement_reports/payout_details/view/tab/line_items.phtml';

    /**
     * Get Column data
     * @return array|mixed
     */
    public function getColumns()
    {
        return array_key_exists('columns', $this->_data) ? $this->_data['columns'] : [];
    }


    /**
     * Get Payout line item details
     *
     * @return mixed
     */
    public function getLineItemDetails()
    {
        return $this->getPayoutDetails()[1];
    }

    /**
     * Get Column names of line items
     *
     * @return mixed
     */
    public function getColumnNames()
    {
        return $this->getLineItemDetails()['header'];
    }

    /**
     * Get Column values of line items
     *
     * @return mixed
     */
    public function getColumnData()
    {
        return $this->getLineItemDetails()['data'];
    }

    /**
     * Get item renderer
     *
     * @return bool|\Magento\Framework\View\Element\AbstractBlock|\Magento\Framework\View\Element\BlockInterface
     * @throws LocalizedException
     */
    public function getItemRenderer()
    {
        $renderer = $this->getChildBlock('default');
        if (!$renderer instanceof \Magento\Framework\View\Element\BlockInterface) {
            throw new \RuntimeException('Renderer for type "' . 'default' . '" does not exist.');
        }
        $renderer->setColumnRenders($this->getLayout()->getGroupChildNames($this->getNameInLayout(), 'column'));

        return $renderer;
    }

    /**
     * Get line item HTML
     *
     * @param array $item
     * @return mixed
     * @throws LocalizedException
     */
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
