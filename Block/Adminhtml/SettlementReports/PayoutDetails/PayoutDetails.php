<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * Class PayoutDetails
 * @package Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails
 */
class PayoutDetails extends Template
{

    /**
     * @var Registry
     */
    private $coreRegistry;
    /**
     * @var Data
     */
    protected $sezzleHelper;

    /**
     * PayoutDetails constructor.
     * @param Context $context
     * @param Data $sezzleHelper
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $sezzleHelper,
        Registry $registry,
        array $data = []
    ) {
        $this->sezzleHelper = $sezzleHelper;
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Get Payout Details
     *
     * @return mixed|null
     */
    public function getPayoutDetails()
    {
        return $this->coreRegistry->registry('payout_details');
    }

}
