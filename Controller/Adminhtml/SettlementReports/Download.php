<?php

namespace Sezzle\Sezzlepay\Controller\Adminhtml\SettlementReports;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Sezzle\Sezzlepay\Api\SettlementReportsManagementInterface;

class Download extends Action
{

    /**
     * @var SettlementReportsManagementInterface
     */
    private $settlementReportsManagement;

    public function __construct(
        Action\Context $context,
        SettlementReportsManagementInterface $settlementReportsManagement
    ) {
        $this->settlementReportsManagement = $settlementReportsManagement;
        parent::__construct($context);
    }


    /**
     * @return ResponseInterface|ResultInterface
     * @throws NoSuchEntityException
     * @throws FileSystemException
     * @throws LocalizedException
     */
    public function execute()
    {
        $payoutUUID = $this->getRequest()->getParam('payout_uuid');
        if (!$payoutUUID) {
            throw new NoSuchEntityException(__("Payout UUID is missing."));
        }
        return $this->settlementReportsManagement->downloadSettlementReportDetails($payoutUUID);
    }
}
