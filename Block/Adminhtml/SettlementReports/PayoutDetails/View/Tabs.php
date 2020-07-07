<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails\View;

/**
 * Class Tabs
 * @package Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails\View
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('sezzle_settlementreports_payoutdetails_view_tabs');
        $this->setDestElementId('sezzle_settlementreports_view');
        $this->setTitle(__('Payout Details View'));
    }
}
