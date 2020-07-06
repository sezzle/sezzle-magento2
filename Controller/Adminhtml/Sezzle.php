<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order;
use Sezzle\Sezzlepay\Api\SettlementReportsManagementInterface;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * Class Sezzle
 * @package Sezzle\Sezzlepay\Controller\AbstractController
 */
abstract class Sezzle extends Action
{
    /**
     * @var SettlementReportsManagementInterface
     */
    private $settlementReportsManagement;
    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var Data
     */
    private $sezzleHelper;

    public function __construct(
        Action\Context $context,
        SettlementReportsManagementInterface $settlementReportsManagement,
        \Magento\Framework\Registry $coreRegistry,
        Data $sezzleHelper,
        PageFactory $resultPageFactory
    ) {
        $this->sezzleHelper = $sezzleHelper;
        $this->coreRegistry = $coreRegistry;
        $this->settlementReportsManagement = $settlementReportsManagement;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Get Order
     *
     * @return bool
     */
    protected function initPayoutDetails()
    {
        $payoutUUID = $this->getRequest()->getParam('payout_uuid');
        $csvData = $this->settlementReportsManagement->getPayoutDetails($payoutUUID);
        if (!$csvData) {
            return false;
        }
        $readableData = $this->sezzleHelper->csvToArray($csvData);
        $this->coreRegistry->register('payout_details', $readableData);
        return true;
    }
}
