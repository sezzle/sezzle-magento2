<?php

namespace Sezzle\Sezzlepay\Plugin\Sales\Controller\Adminhtml\Order;

use Exception;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\View;
use Magento\Sales\Model\Order;
use Sezzle\Sezzlepay\Model\Sezzle;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleConfigInterface;

class ViewPlugin
{
    /**
     * @var Sezzle
     */
    private $sezzleModel;

    /**
     * @var SezzleConfigInterface
     */
    private $sezzleConfig;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param Sezzle $sezzleModel
     * @param SezzleConfigInterface $sezzleConfig
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Sezzle                   $sezzleModel,
        SezzleConfigInterface    $sezzleConfig
    )
    {
        $this->orderRepository = $orderRepository;
        $this->sezzleModel = $sezzleModel;
        $this->sezzleConfig = $sezzleConfig;
    }

    /**
     * @param View $subject
     * @return void
     */
    public function beforeExecute(View $subject)
    {
        try {
            $order = $this->orderRepository->get($subject->getRequest()->getParam('order_id'));
        } catch (Exception $e) {
            //Early exit, this exception will be thrown later in the request and exit before any of this
            //plugin's modifications are required
            return null;
        }

        $order->setActionFlag(
            Order::ACTION_FLAG_INVOICE,
            $this->sezzleModel->canInvoice($order)
            || $this->sezzleConfig->isTokenizationAllowed()
        );
        return null;
    }
}
