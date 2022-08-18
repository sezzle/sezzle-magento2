<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Sezzle\Sezzlepay\Api\SettlementReportsManagementInterface;

/**
 * Class SettlementReports
 * @package Sezzle\Sezzlepay\Controller\Adminhtml
 */
abstract class SettlementReports extends Action
{
    /**
     * @var SettlementReportsManagementInterface
     */
    private $settlementReportsManagement;
    /**
     * @var Registry
     */
    private $coreRegistry;
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param SettlementReportsManagementInterface $settlementReportsManagement
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Action\Context                       $context,
        SettlementReportsManagementInterface $settlementReportsManagement,
        Registry                             $coreRegistry,
        PageFactory                          $resultPageFactory
    )
    {
        $this->coreRegistry = $coreRegistry;
        $this->settlementReportsManagement = $settlementReportsManagement;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Get Payout Details
     *
     * @return bool
     * @throws LocalizedException
     */
    protected function initPayoutDetails(): bool
    {
        try {
            if (!$payoutUUID = $this->getRequest()->getParam('payout_uuid')) {
                return false;
            }
            $data = $this->settlementReportsManagement->getPayoutDetails($payoutUUID);
            if (!$data) {
                return false;
            }
            $this->coreRegistry->register('payout_details', $data);
            return true;
        } catch (NoSuchEntityException|LocalizedException $e) {
            throw new LocalizedException(__("Unable to get payout details."));
        }
    }
}
