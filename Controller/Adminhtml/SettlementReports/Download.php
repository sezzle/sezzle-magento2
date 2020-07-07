<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Controller\Adminhtml\SettlementReports;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Sezzle\Sezzlepay\Api\SettlementReportsManagementInterface;

/**
 * Class Download
 * @package Sezzle\Sezzlepay\Controller\Adminhtml\SettlementReports
 */
class Download extends Action
{

    /**
     * @var SettlementReportsManagementInterface
     */
    private $settlementReportsManagement;

    /**
     * Download constructor.
     * @param Action\Context $context
     * @param SettlementReportsManagementInterface $settlementReportsManagement
     */
    public function __construct(
        Action\Context $context,
        SettlementReportsManagementInterface $settlementReportsManagement
    ) {
        $this->settlementReportsManagement = $settlementReportsManagement;
        parent::__construct($context);
    }


    /**
     * Download Payout Details
     *
     * @return ResponseInterface|ResultInterface
     * @throws Exception
     */
    public function execute()
    {
        $payoutUUID = $this->getRequest()->getParam('payout_uuid');
        try {
            if (!$payoutUUID) {
                throw new LocalizedException(
                    __('Payout UUID is missing.')
                );
            }
            return $this->settlementReportsManagement->downloadSettlementReportDetails($payoutUUID);
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('*/*/index');
        }
    }
}
