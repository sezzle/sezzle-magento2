<?php

namespace Sezzle\Sezzlepay\Controller\Adminhtml\SettlementReports;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Customers list action
     *
     * @return Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        /**
         * Set active menu item
         */
        $resultPage->setActiveMenu('Sezzle_Sezzlepay::sezzle_settlement_reports');
        $resultPage->getConfig()->getTitle()->prepend(__('Settlement Reports'));

        /**
         * Add breadcrumb item
         */
        $resultPage->addBreadcrumb(__('Sezzle'), __('Settlement Reports'));
        $resultPage->addBreadcrumb(__('Manage Settlement Reports'), __('Manage Settlement Reports'));

        return $resultPage;
    }
}
