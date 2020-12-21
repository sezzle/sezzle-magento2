<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Controller\Adminhtml\SettlementReports;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\Page;
use Sezzle\Sezzlepay\Controller\Adminhtml\SettlementReports;

/**
 * Class View
 * @package Sezzle\Sezzlepay\Controller\Adminhtml\SettlementReports
 */
class View extends SettlementReports
{

    /**
     * Payout Details view
     *
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        try {
            $payoutDetails = $this->initPayoutDetails();
            if (!$payoutDetails) {
                throw new LocalizedException(
                    __('Exception occurred during report load.')
                );
            }
            $resultPage = $this->resultPageFactory->create();
            $resultPage->setActiveMenu('Sezzle_Sezzlepay::sezzle_settlement_reports');
            $resultPage->addBreadcrumb(__('Payout Details'), __('Payout Details'));
            $resultPage->addBreadcrumb(__('Sezzle Settlement Reports'), __('Sezzle Settlement Reports'));
            $resultPage->getConfig()->getTitle()->prepend('Payout Details');
            return $resultPage;
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('*/*/index');
        }
    }
}
