<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Plugin\Sales\Controller\Adminhtml\Order\Invoice;

use Closure;
use Exception;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\Invoice\Save;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\Ui\ConfigProvider;

/**
 * Class SavePlugin
 * @package Sezzle\Sezzlepay\Plugin\Sales\Controller\Adminhtml\Order\Invoice
 */
class SavePlugin
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var ManagerInterface
     */
    private $messageManager;
    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var Data
     */
    private $sezzleHelper;

    /**
     * SavePlugin constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param ManagerInterface $messageManager
     * @param RedirectFactory $resultRedirectFactory
     * @param Data $sezzleHelper
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ManagerInterface         $messageManager,
        RedirectFactory          $resultRedirectFactory,
        Data                     $sezzleHelper
    )
    {
        $this->orderRepository = $orderRepository;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->sezzleHelper = $sezzleHelper;
    }

    /**
     * Capture case check, if offline mode, don't allow
     *
     * @param Save $subject
     * @param Closure $proceed
     * @return Redirect|null
     */
    public function aroundExecute(Save $subject, Closure $proceed): ?Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $data = $subject->getRequest()->getPost('invoice');
        $orderId = $subject->getRequest()->getParam('order_id');

        $captureMethod = $data['capture_case'] ?? '';

        $this->sezzleHelper->logSezzleActions('Capture method: ' . $captureMethod);

        try {
            $order = $this->orderRepository->get($orderId);
        } catch (Exception $e) {
            $this->sezzleHelper->logSezzleActions('Unable to get order. Exception: ' . $e->getMessage());
            return $proceed();
        }

        if ($order->getPayment()->getMethod() !== ConfigProvider::CODE) {
            return $proceed();
        }

        if ($captureMethod === 'offline') {
            $this->messageManager
                ->addErrorMessage(__("'Capture Offline' is not allowed. Please use 'Capture Online' option."));
            return $resultRedirect->setPath('sales/*/new', ['order_id' => $orderId]);
        }

        return $proceed();
    }
}
