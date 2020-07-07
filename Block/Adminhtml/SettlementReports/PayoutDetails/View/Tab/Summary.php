<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails\View\Tab;

use Magento\Backend\Block\Widget\Tab\TabInterface;
use Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails\PayoutDetails;

/**
 * Class Summary
 * @package Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails\View\Tab
 */
class Summary extends PayoutDetails implements
    TabInterface
{

    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'Sezzle_Sezzlepay::settlement_reports/payout_details/view/tab/summary.phtml';

    /**
     * Get Payout Summary
     *
     * @return mixed
     */
    public function getSummary()
    {
        return $this->getPayoutDetails()[0];
    }

    /**
     * Get column headers
     *
     * @return mixed
     */
    public function getColumnNames()
    {
        return $this->getSummary()['header'];
    }

    /**
     * Get column name
     *
     * @param string $name
     * @return string
     */
    public function getColumnName($name)
    {
        return $this->sezzleHelper->snakeCaseToTitleCase($name);
    }

    /**
     * @return mixed
     */
    public function getColumnData()
    {
        return $this->getSummary()['data'][0];
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Summary');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Payout Summary');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}
