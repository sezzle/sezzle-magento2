<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Controller\Adminhtml\SettlementReports;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 * @package Sezzle\Sezzlepay\Controller\Adminhtml\SettlementReports
 */
class Index extends Action
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * Index constructor.
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Settlement Reports Grid
     *
     * @return Page
     */
    public function execute(): Page
    {

        $resultPage = $this->resultPageFactory->create();
        /**
         * Set active menu item
         */
        $resultPage->setActiveMenu('Sezzle_Sezzlepay::sezzle_settlement_reports');
        $resultPage->getConfig()->getTitle()->prepend(__('Sezzle Settlement Reports'));

        /**
         * Add breadcrumb item
         */
        $resultPage->addBreadcrumb(__('Sezzle'), __('Sezzle Settlement Reports'));

        return $resultPage;
    }
}
