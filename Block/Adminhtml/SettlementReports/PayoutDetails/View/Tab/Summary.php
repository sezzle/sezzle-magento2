<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails\View\Tab;

use Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails\PayoutDetails;

/**
 * Order information tab
 *
 * @api
 * @author      Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Summary extends PayoutDetails implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{

    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'Sezzle_Sezzlepay::settlement_reports/payout_details/view/tab/summary.phtml';

    public function getSummary()
    {
        return $this->getPayoutDetails()[0];
    }

    public function getColumnNames()
    {
        return $this->getSummary()['header'];
    }

    public function getColumnName($name)
    {
        return $this->sezzleHelper->snakeCaseToTitleCase($name);
    }

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
