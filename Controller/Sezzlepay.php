<?php
namespace Sezzle\Sezzlepay\Controller;

abstract class Sezzlepay extends \Magento\Framework\App\Action\Action
{
    protected $_customerSession;
    protected $_checkoutSession;
    protected $_orderFactory;
    protected $_orderHistoryFactory;
    protected $_sezzlepayModel;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Sezzle\Sezzlepay\Model\SezzlePaymentMethod $sezzlepayModel
    ) {
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_orderHistoryFactory = $orderHistoryFactory;
        $this->_sezzlepayModel = $sezzlepayModel;
        parent::__construct($context);
    }

    protected function getSezzlepayModel()
    {
        return $this->_sezzlepayModel;
    }

    protected function getOrderById($order_id)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->get('Magento\Sales\Model\Order');
        $order_info = $order->loadByIncrementId($order_id);
        return $order_info;
    }

    protected function getOrder()
    {
        return $this->_orderFactory->create()->loadByIncrementId(
            $this->_checkoutSession->getLastRealOrderId()
        );
    }

}