<?php


namespace Sezzle\Sezzlepay\Block\Adminhtml\SettlementReports\PayoutDetails;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Sezzle\Sezzlepay\Helper\Data;

class PayoutDetails extends Template
{

    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;
    /**
     * @var Data
     */
    protected $sezzleHelper;

    public function __construct(
        Context $context,
        Data $sezzleHelper,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->sezzleHelper = $sezzleHelper;
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    public function getPayoutDetails()
    {
        return $this->coreRegistry->registry('payout_details');
    }

}
