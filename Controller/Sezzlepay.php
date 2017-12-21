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
    protected $_jsonHelper;
    protected $_quoteManagement;
    protected $_transactionBuilder;
    protected $_resultJsonFactory;

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
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder
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
        $this->_jsonHelper = $jsonHelper;
        $this->_quoteManagement = $quoteManagement;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_resultJsonFactory = $resultJsonFactory;
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

    public function cancelOrder($order, $comment)
    {
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $order->registerCancellation($comment)->save();
        }
    }

    public function updatePayment($order, $sezzleId)
    {
        $this->_logger->info("Updating payment");
        $payment = $order->getPayment();
        $payment->setTransactionId($sezzleId);
        $payment->setAdditionalInformation(\Sezzle\Sezzlepay\Model\SezzlePaymentMethod::ADDITIONAL_INFORMATION_KEY_ORDERID, $sezzleId);
        $payment->save();
        $this->_logger->info("Saved payment");
    }

    public function createInvoice($order)
    {
        // create invoice
        if ($order->canInvoice()) {
            $this->_logger->info("Creating invoice");
            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
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

    public function _createTransaction($order, $reference)
    {
        $payment = $order->getPayment();
        $payment->setLastTransId($reference);
        $payment->setTransactionId($reference);
        $formatedPrice = $order->getBaseCurrency()->formatTxt(
            $order->getGrandTotal()
        );

        $message = __('The authorized amount is %1.', $formatedPrice);

        $trans = $this->_transactionBuilder;
        $transaction = $trans->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($reference)
            ->setFailSafe(true)
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

        $payment->addTransactionCommentsToOrder(
            $transaction,
            $message
        );
        $payment->setParentTransactionId(null);
        $payment->save();
        $order->save();

        return $transaction->save()->getTransactionId();
    }
}
