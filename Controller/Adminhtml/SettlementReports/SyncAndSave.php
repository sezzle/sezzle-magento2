<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Controller\Adminhtml\SettlementReports;

use Exception;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Sezzle\Sezzlepay\Api\SettlementReportsManagementInterface;

/**
 * Class SyncAndSave
 * @package Sezzle\Sezzlepay\Controller\Adminhtml\SettlementReports
 */
class SyncAndSave extends Action
{
    /**
     * @var SettlementReportsManagementInterface
     */
    private $settlementReportsManagement;
    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * SyncAndSave constructor.
     * @param Action\Context $context
     * @param DateTime $dateTime
     * @param SettlementReportsManagementInterface $settlementReportsManagement
     */
    public function __construct(
        Action\Context                       $context,
        DateTime                             $dateTime,
        SettlementReportsManagementInterface $settlementReportsManagement
    )
    {
        parent::__construct($context);
        $this->dateTime = $dateTime;
        $this->settlementReportsManagement = $settlementReportsManagement;
    }

    /**
     * Report sync and save action
     *
     * @return Redirect
     * @throws Exception
     */
    public function execute(): Redirect
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        try {
            $postData = $this->getRequest()->getPostValue();
            $fromDate = isset($postData['from_date']) ? $this->dateTime->date('Y-m-d', $postData['from_date']) : null;
            $toDate = isset($postData['to_date']) ? $this->dateTime->date('Y-m-d', $postData['to_date']) : null;
            $this->settlementReportsManagement->syncAndSave($fromDate, $toDate);
        } catch (NotFoundException $e) {
            $this->messageManager->addNoticeMessage(__('No reports found to sync.'));
            return $resultRedirect->setPath('*/*/index');
        } catch (InputException $e) {
            $this->messageManager->addNoticeMessage(__('Invalid params provided.'));
            return $resultRedirect->setPath('*/*/index');
        } catch (NoSuchEntityException|LocalizedException $e) {
            $this->messageManager->addErrorMessage(__('Unable to sync your reports. Please try again.'));
            return $resultRedirect->setPath('*/*/index');
        }
        $this->messageManager->addSuccessMessage(__('Latest reports has been synced successfully.'));
        return $resultRedirect->setPath('*/*/index');
    }
}
