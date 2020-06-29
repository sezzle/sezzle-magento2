<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Plugin\Sales\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Registry;
use Magento\Framework\Translate\InlineInterface;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\View;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\Sezzle;

class ViewPlugin extends View
{
    /**
     * @var Data
     */
    private $sezzleHelper;
    /**
     * @var Sezzle
     */
    private $sezzleModel;

    public function __construct(
        Action\Context $context,
        Registry $coreRegistry,
        FileFactory $fileFactory,
        InlineInterface $translateInline,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        LayoutFactory $resultLayoutFactory,
        RawFactory $resultRawFactory,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        Data $sezzleHelper,
        Sezzle $sezzleModel
    ) {
        $this->sezzleHelper = $sezzleHelper;
        $this->sezzleModel = $sezzleModel;
        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $translateInline,
            $resultPageFactory,
            $resultJsonFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $orderManagement,
            $orderRepository,
            $logger
        );
    }

    /**
     * Save invoice
     *
     * @param View $subject
     * @param \Closure $proceed
     * @return Page|Redirect
     */
    public function aroundExecute(
        View $subject,
        \Closure $proceed
    ) {
        $order = $this->_initOrder();
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($order) {
            try {
                $order->setActionFlag(Order::ACTION_FLAG_INVOICE, $this->sezzleModel->canInvoice($order));
                $resultPage = $this->_initAction();
                $resultPage->getConfig()->getTitle()->prepend(__('Orders'));
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addErrorMessage(__('Exception occurred during order load'));
                $resultRedirect->setPath('sales/order/index');
                return $resultRedirect;
            }
            $resultPage->getConfig()->getTitle()->prepend(sprintf("#%s", $order->getIncrementId()));
            return $resultPage;
        }
        $resultRedirect->setPath('sales/*/');
        return $resultRedirect;
    }
}
