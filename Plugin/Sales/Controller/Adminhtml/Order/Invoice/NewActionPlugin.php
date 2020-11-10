<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Plugin\Sales\Controller\Adminhtml\Order\Invoice;

use Closure;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\Invoice\NewAction;
use Magento\Sales\Model\Order;
use Sezzle\Sezzlepay\Model\Sezzle;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleConfigInterface;
use Sezzle\Sezzlepay\Model\Tokenize;

/**
 * Class NewActionPlugin
 * @package Sezzle\Sezzlepay\Plugin\Sales\Controller\Adminhtml\Order\Invoice
 */
class NewActionPlugin
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepositoryInterface;
    /**
     * @var Sezzle
     */
    private $sezzleModel;
    /**
     * @var ManagerInterface
     */
    private $messageManager;
    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var SezzleConfigInterface
     */
    private $sezzleConfig;

    /**
     * NewActionPlugin constructor.
     * @param OrderRepositoryInterface $orderRepositoryInterface
     * @param Sezzle $sezzleModel
     * @param ManagerInterface $messageManager
     * @param RedirectFactory $resultRedirectFactory
     * @param RequestInterface $request
     * @param SezzleConfigInterface $sezzleConfig
     */
    public function __construct(
        OrderRepositoryInterface $orderRepositoryInterface,
        Sezzle $sezzleModel,
        ManagerInterface $messageManager,
        RedirectFactory $resultRedirectFactory,
        RequestInterface $request,
        SezzleConfigInterface $sezzleConfig
    ) {
        $this->sezzleModel = $sezzleModel;
        $this->orderRepositoryInterface = $orderRepositoryInterface;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->request = $request;
        $this->sezzleConfig = $sezzleConfig;
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
        $orderId = $this->request->getParam('order_id');

        try {
            /** @var Order $order */
            $order = $this->orderRepositoryInterface->get($orderId);
            $isTokenizedOrder = $order->getPayment()->getAdditionalInformation(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID);
            if ($order->getPayment()->getMethod() === Sezzle::PAYMENT_CODE
                && (!$this->sezzleModel->canInvoice($order)
                    && !$isTokenizedOrder)) {
                throw new LocalizedException(
                    __(!$isTokenizedOrder
                        ? 'Authorization expired. Requires a tokenized customer for creating invoice.'
                        : 'Authorization expired. Invoice cannot be created anymore.')
                );
            }
            return $proceed();
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
            return $resultRedirect;
        }
    }
}
