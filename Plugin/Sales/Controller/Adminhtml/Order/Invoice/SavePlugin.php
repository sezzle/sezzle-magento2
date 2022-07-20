<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Plugin\Sales\Controller\Adminhtml\Order\Invoice;

use Closure;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\Invoice\Save;
use Sezzle\Sezzlepay\Model\Sezzle;

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
     * SavePlugin constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param ManagerInterface $messageManager
     * @param RedirectFactory $resultRedirectFactory
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ManagerInterface         $messageManager,
        RedirectFactory          $resultRedirectFactory
    )
    {
        $this->orderRepository = $orderRepository;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
    }

    /**
     * Capture case check, if offline mode, don't allow
     *
     * @param Save $subject
     * @param Closure $proceed
     * @return Redirect|null
     */
    public function aroundExecute(Save $subject, Closure $proceed)
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $data = $subject->getRequest()->getPost('invoice');
        $orderId = $subject->getRequest()->getParam('order_id');

        $order = $this->orderRepository->get($orderId);

        if ($order->getPayment()->getMethod() !== Sezzle::PAYMENT_CODE) {
            return $proceed();
        }

        if (isset($data['capture_case']) && $data['capture_case'] === 'offline') {
            $this->messageManager
                ->addErrorMessage(__("'Capture Offline' is not allowed. Please use 'Capture Online' option."));
            return $resultRedirect->setPath('sales/*/new', ['order_id' => $orderId]);
        }

        return $proceed();
    }
}
