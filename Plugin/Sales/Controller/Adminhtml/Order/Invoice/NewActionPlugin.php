<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Plugin\Sales\Controller\Adminhtml\Order\Invoice;

use Closure;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface as SezzlePluginOrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\Invoice\NewAction;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;
use Sezzle\Sezzlepay\Model\Sezzle;

/**
 * Class NewActionPlugin
 * @package Sezzle\Sezzlepay\Plugin\Sales\Controller\Adminhtml\Order\Invoice
 */
class NewActionPlugin extends NewAction
{
    /**
     * @var SezzlePluginOrderRepositoryInterface
     */
    private $sezzlePluginOrderRepositoryInterface;
    /**
     * @var Sezzle
     */
    private $sezzleModel;

    /**
     * NewActionPlugin constructor.
     * @param Action\Context $context
     * @param Registry $registry
     * @param PageFactory $resultPageFactory
     * @param InvoiceService $invoiceService
     * @param SezzlePluginOrderRepositoryInterface $sezzlePluginOrderRepositoryInterface
     * @param Sezzle $sezzleModel
     * @param SezzlePluginOrderRepositoryInterface|null $orderRepository
     */
    public function __construct(
        Action\Context $context,
        Registry $registry,
        PageFactory $resultPageFactory,
        InvoiceService $invoiceService,
        SezzlePluginOrderRepositoryInterface $sezzlePluginOrderRepositoryInterface,
        Sezzle $sezzleModel,
        OrderRepositoryInterface $orderRepository = null
    ) {
        $this->sezzleModel = $sezzleModel;
        $this->sezzlePluginOrderRepositoryInterface = $sezzlePluginOrderRepositoryInterface;
        parent::__construct($context, $registry, $resultPageFactory, $invoiceService, $orderRepository);
    }

    /**
     * Auth Check
     *
     * @param NewAction $subject
     * @param Closure $proceed
     * @return Redirect
     */
    public function aroundExecute(
        NewAction $subject,
        Closure $proceed
    ) {
        $orderId = $this->getRequest()->getParam('order_id');

        try {
            /** @var Order $order */
            $order = $this->sezzlePluginOrderRepositoryInterface->get($orderId);
            if ($order->getPayment()->getMethod() === Sezzle::PAYMENT_CODE
                    && !$this->sezzleModel->canInvoice($order)) {
                throw new LocalizedException(
                    __('Authorization expired. Invoice cannot be created anymore.')
                );
            }
            return $proceed();
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            return $this->_redirectToOrder($orderId);
        }
    }
}
