<?php
namespace Sezzle\Sezzlepay\Controller;
use Magento\Sales\Model\Order;
abstract class Sezzlepay extends \Magento\Framework\App\Action\Action
{
    protected $_customerSession;
    protected $_checkoutSession;
    protected $_orderFactory;
    protected $_orderHistoryFactory;
    protected $_sezzlepayModel;
    protected $_salesOrderConfig;
    protected $_invoiceService;
    protected $_transactionFactory;
    protected $_logger;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Sezzle\Sezzlepay\Model\SezzlePaymentMethod $sezzlepayModel,
        \Magento\Sales\Model\Order\Config $salesOrderConfig,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\DB\TransactionFactory $transactionFactory
    ) {
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_orderHistoryFactory = $orderHistoryFactory;
        $this->_sezzlepayModel = $sezzlepayModel;
        $this->_salesOrderConfig = $salesOrderConfig;
        $this->_invoiceService = $invoiceService;
        $this->_transactionFactory = $transactionFactory;
        $this->_logger = $logger;
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

    public function cancelOrder($order, $comment) {
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $order->registerCancellation($comment)->save();
        }
    }

    public function updatePayment($order, $sezzleId) {
        $this->_logger->info("Updating payment");
        $payment = $order->getPayment();
        $payment->setTransactionId($sezzleId);
        $payment->setAdditionalInformation(\Sezzle\Sezzlepay\Model\SezzlePaymentMethod::ADDITIONAL_INFORMATION_KEY_ORDERID, $sezzleId);
        $payment->save();
        $this->_logger->info("Saved payment");
    }

    public function createInvoice($order) {
        // create invoice
        $this->_logger->info("Creating invoice");
        $invoice = $this->_invoiceService->prepareInvoice($order);
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
        $invoice->register();
        $this->_logger->info("Created invoice");

        $this->_logger->info("Creating transaction");
        $transaction = $this->_transactionFactory->create();
        $transaction->addObject($order)
            ->addObject($invoice)
            ->addObject($invoice->getOrder())
            ->save();
        $this->_logger->info("Saved transaction");
    }
}