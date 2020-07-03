<?php

namespace Sezzle\Sezzlepay\Controller\Adminhtml\SettlementReports;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Redirect;
use Sezzle\Sezzlepay\Api\SettlementReportsManagementInterface;

class SyncAndSave extends Action
{
    /**
     * @var SettlementReportsManagementInterface
     */
    private $settlementReportsManagement;

    public function __construct(
        Action\Context $context,
        SettlementReportsManagementInterface $settlementReportsManagement
    ) {
        parent::__construct($context);
        $this->settlementReportsManagement = $settlementReportsManagement;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return Redirect
     */
    public function execute()
    {
        $this->settlementReportsManagement->syncAndSave();
        $this->messageManager->addSuccessMessage(__('Latest reports has been synced successfully.'));
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setRefererOrBaseUrl();
        return $resultRedirect;
    }
}
