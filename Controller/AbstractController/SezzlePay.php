<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Controller\AbstractController;

use Magento\Framework\App\Action\Action;
use Magento\Sales\Model\Order;

/**
 * Class Sezzlepay
 * @package Sezzle\Sezzlepay\Controller\AbstractController
 */
abstract class SezzlePay extends Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;
    /**
     * @var Order\Status\HistoryFactory
     */
    protected $_orderHistoryFactory;
    /**
     * @var \Sezzle\Sezzlepay\Model\SezzlePay
     */
    protected $_sezzlepayModel;
    /**
     * @var Order\Config
     */
    protected $_salesOrderConfig;
    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $_invoiceService;
    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $_transactionFactory;
    /**
     * @var Order\Email\Sender\OrderSender
     */
    protected $_orderSender;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;
    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $_quoteManagement;
    /**
     * @var Order\Payment\Transaction\BuilderInterface
     */
    protected $_transactionBuilder;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * Sezzlepay constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param Order\Status\HistoryFactory $orderHistoryFactory
     * @param \Sezzle\Sezzlepay\Model\SezzlePay $sezzlepayModel
     * @param Order\Config $salesOrderConfig
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\DB\TransactionFactory $transactionFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param Order\Payment\Transaction\BuilderInterface $transactionBuilder
     * @param Order\Email\Sender\OrderSender $orderSender
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\Status\HistoryFactory $orderHistoryFactory,
        \Sezzle\Sezzlepay\Model\SezzlePay $sezzlepayModel,
        \Magento\Sales\Model\Order\Config $salesOrderConfig,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
    )
    {
        $this->_customerSession = $customerSession;
        $this->_jsonHelper = $jsonHelper;
        $this->_customerRepository = $customerRepository;
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
        $this->_orderSender = $orderSender;
        $this->_resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    protected function getOrder()
    {
        return $this->_orderFactory->create()->loadByIncrementId(
            $this->_checkoutSession->getLastRealOrderId()
        );
    }
}
