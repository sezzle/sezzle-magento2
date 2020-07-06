<?php
namespace Sezzle\Sezzlepay\Controller\Adminhtml\SettlementReports;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Sezzle\Sezzlepay\Controller\Adminhtml\Sezzle;

class View extends Sezzle
{

    /**
     * @return ResponseInterface|ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $payoutDetails = $this->initPayoutDetails();
        if (!$payoutDetails) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $this->messageManager->addErrorMessage(__('Exception occurred during report load'));
            $resultRedirect->setRefererOrBaseUrl();
            return $resultRedirect;
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Sezzle_Sezzlepay::sezzle_settlement_reports');
        $resultPage->setActiveMenu('Sezzle_Sezzlepay::sezzle');
        $resultPage->getConfig()->getTitle()->prepend('Payout Details');
        return $resultPage;
    }
}
