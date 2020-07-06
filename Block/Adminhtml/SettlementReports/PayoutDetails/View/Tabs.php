<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails\View;

/**
 * Order view tabs
 *
 * @api
 * @since 100.0.2
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $jsonEncoder, $authSession, $data);
    }

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
