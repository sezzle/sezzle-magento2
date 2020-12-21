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
use Sezzle\Sezzlepay\Helper\Data;

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
     * @var Data
     */
    private $sezzleHelper;

    public function __construct(
        Action\Context $context,
        SettlementReportsManagementInterface $settlementReportsManagement,
        Registry $coreRegistry,
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
     * Get Payout Details
     *
     * @return bool
     * @throws LocalizedException
     */
    protected function initPayoutDetails()
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
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__("Unable to get payout details."));
        } catch (LocalizedException $e) {
            throw new LocalizedException(__("Unable to get payout details."));
        }
    }
}
