<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Plugin\Sales\Controller\Adminhtml\Order\Invoice;

use Magento\Backend\Model\Session as BackendSession;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\ShipmentSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Sales\Model\Service\InvoiceService;
use Psr\Log\LoggerInterface;
use Sezzle\Payment\Model\Sezzle;

class SavePlugin
{
    const SUCCESS_CODE = 200;
    const CAPTURE_ONLINE = 'online';
    const MERCHANT_SUPPORT_URL = "https://sezzle.com/contact-us/merchant-support";
    const CAPTURE_ERROR_MSG = "Capture time for this order has been expired";

    private $order = null;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     *
     * @var sezzle
     */
    private $sezzle;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * @var ShipmentSender
     */
    protected $shipmentSender;

    /**
     * @var ShipmentFactory
     */
    protected $shipmentFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var ManagerInterface
    */
    private $messageManager;

    /**
     * @var RedirectFactory
    */
    private $resultRedirectFactory;

    /**
     * @var Validator
    */
    private $formKeyValidator;

    /**
     * @var BackendSession
    */
    private $backendSession;

    /**
     * @var Transaction
    */
    private $transaction;

    /**
     * @var LoggerInterface
    */
    private $logger;

    /**
     *
     * @param sezzle $sezzle
     * @param OrderRepositoryInterface $orderRepository
     * @param DateTime $dateTime
     * @param Registry $registry
     * @param InvoiceSender $invoiceSender
     * @param ShipmentSender $shipmentSender
     * @param ShipmentFactory $shipmentFactory
     * @param InvoiceService $invoiceService
     * @param ManagerInterface $messageManager
     * @param RedirectFactory $resultRedirectFactory
     * @param Validator $formKeyValidator
     * @param BackendSession $backendSession
     * @param Transaction $transaction
     * @param LoggerInterface $logger
     */
    public function __construct(
        sezzle $sezzle,
        OrderRepositoryInterface $orderRepository,
        DateTime $dateTime,
        Registry $registry,
        InvoiceSender $invoiceSender,
        ShipmentSender $shipmentSender,
        ShipmentFactory $shipmentFactory,
        InvoiceService $invoiceService,
        ManagerInterface $messageManager,
        RedirectFactory $resultRedirectFactory,
        Validator $formKeyValidator,
        BackendSession $backendSession,
        Transaction $transaction,
        LoggerInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->sezzle = $sezzle;
        $this->dateTime = $dateTime;
        $this->registry = $registry;
        $this->invoiceSender = $invoiceSender;
        $this->shipmentSender = $shipmentSender;
        $this->shipmentFactory = $shipmentFactory;
        $this->invoiceService = $invoiceService;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->backendSession = $backendSession;
        $this->transaction = $transaction;
        $this->logger = $logger;
    }

    /**
     * Prepare shipment
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return \Magento\Sales\Model\Order\Shipment|false
     */
    protected function _prepareShipment($invoice, $invoicePostData, $tracking)
    {
        $shipment = $this->shipmentFactory->create(
            $invoice->getOrder(),
            isset($invoicePostData['items']) ? $invoicePostData['items'] : [],
            $tracking
        );

        if (!$shipment->getTotalQty()) {
            return false;
        }

        return $shipment->register();
    }

    /**
     * Save invoice
     *
     * @param \Magento\Sales\Controller\Adminhtml\Order\Invoice\Save $subject
     * @param \Closure $proceed
     * @return void
     */
    public function aroundExecute(
        \Magento\Sales\Controller\Adminhtml\Order\Invoice\Save $subject,
        \Closure $proceed
    ) {
        return $proceed();
//        $orderId = $subject->getRequest()->getParam('order_id');
//        $order = $this->orderRepository->get($orderId);
//        $this->order = !$this->order ? $order : $this->order;
//        if ($order->getPayment()->getMethodInstance()->getCode() == Sezzle::PAYMENT_CODE) {
//            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
//            $resultRedirect = $this->resultRedirectFactory->create();
//
//            $formKeyIsValid = $this->formKeyValidator->validate($subject->getRequest());
//            $isPost = $subject->getRequest()->isPost();
//            if (!$formKeyIsValid || !$isPost) {
//                $this->messageManager
//                    ->addErrorMessage(__("The invoice can't be saved at this time. Please try again later."));
//                return $resultRedirect->setPath('sales/order/index');
//            }
//
//            $data = $subject->getRequest()->getPost('invoice');
//
//            if (!empty($data['comment_text'])) {
//                $this->backendSession->setCommentText($data['comment_text']);
//            }
//
//            try {
//                $invoiceData = $subject->getRequest()->getParam('invoice', []);
//                $invoiceItems = isset($invoiceData['items']) ? $invoiceData['items'] : [];
//                if (!$order->getId()) {
//                    throw new \Magento\Framework\Exception\LocalizedException(__('The order no longer exists.'));
//                }
//
//                if (!$order->canInvoice()) {
//                    throw new \Magento\Framework\Exception\LocalizedException(
//                        __('The order does not allow an invoice to be created.')
//                    );
//                }
//
//                $invoice = $this->invoiceService->prepareInvoice($order, $invoiceItems);
//
//                if (!$invoice) {
//                    throw new LocalizedException(__("The invoice can't be saved at this time. Please try again later."));
//                }
//
//                if (!$invoice->getTotalQty()) {
//                    throw new \Magento\Framework\Exception\LocalizedException(
//                        __("The invoice can't be created without products. Add products and try again.")
//                    );
//                }
//                $this->registry->register('current_invoice', $invoice);
//                if (!empty($data['capture_case'])) {
//                    if ($this->order->getId() && $data['capture_case'] == self::CAPTURE_ONLINE) {
//                        $this->handleCaptureAction($invoice);
//                    }
//                }
//
//                if (!empty($data['comment_text'])) {
//                    $invoice->addComment(
//                        $data['comment_text'],
//                        isset($data['comment_customer_notify']),
//                        isset($data['is_visible_on_front'])
//                    );
//
//                    $invoice->setCustomerNote($data['comment_text']);
//                    $invoice->setCustomerNoteNotify(isset($data['comment_customer_notify']));
//                }
//
//                $invoice->register();
//
//                $invoice->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
//                $invoice->getOrder()->setIsInProcess(true);
//
//                $transactionSave = $this->transaction
//                ->addObject(
//                    $invoice
//                )->addObject(
//                    $invoice->getOrder()
//                );
//                $shipment = false;
//                if (!empty($data['do_shipment']) || (int)$invoice->getOrder()->getForcedShipmentWithInvoice()) {
//                    $tracking = $subject->getRequest()->getPost('tracking');
//                    $shipment = $this->_prepareShipment($invoice, $data, $tracking);
//                    if ($shipment) {
//                        $transactionSave->addObject($shipment);
//                    }
//                }
//                $transactionSave->save();
//
//                // send invoice/shipment emails
//                try {
//                    if (!empty($data['send_email'])) {
//                        $this->invoiceSender->send($invoice);
//                    }
//                } catch (\Exception $e) {
//                    $this->logger->critical($e);
//                    $this->messageManager->addErrorMessage(__('We can\'t send the invoice email right now.'));
//                }
//                if ($shipment) {
//                    try {
//                        if (!empty($data['send_email'])) {
//                            $this->shipmentSender->send($shipment);
//                        }
//                    } catch (\Exception $e) {
//                        $this->logger->critical($e);
//                        $this->messageManager->addErrorMessage(__('We can\'t send the shipment right now.'));
//                    }
//                }
//                if (!empty($data['do_shipment'])) {
//                    $this->messageManager->addSuccessMessage(__('You created the invoice and shipment.'));
//                } else {
//                    $this->messageManager->addSuccessMessage(__('The invoice has been created.'));
//                }
//                $this->backendSession->getCommentText(true);
//                return $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
//            } catch (LocalizedException $e) {
//                $this->messageManager->addComplexErrorMessage(
//                    'addCaptureErrorMessage',
//                    [
//                        'original_msg' => self::CAPTURE_ERROR_MSG == $e->getMessage() ? false : $e->getMessage(),
//                        'support_url' => self::MERCHANT_SUPPORT_URL
//                    ]
//                );
//            } catch (\Exception $e) {
//                $this->messageManager->addErrorMessage(
//                    __("The invoice can't be saved at this time. Please try again later.")
//                );
//                $this->logger->critical($e->getMessage());
//            }
//            return $resultRedirect->setPath('sales/*/new', ['order_id' => $orderId]);
//        } else {
//            return $proceed();
//        }
    }

    /**
     * Capture handle action
     *
     * @param mixed $invoice
     * @return void
     * @throws LocalizedException
     */
    private function handleCaptureAction($invoice)
    {
        $captureExpirationTimestamp = $this->dateTime->timestamp(
            $this->order->getPayment()
            ->getAdditionalInformation(sezzle::SEZZLE_CAPTURE_EXPIRY)
        );
        $reference = $this->order->getPayment()->getAdditionalInformation(sezzle::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID);
        $currentTime = $this->dateTime->gmtDate("Y-m-d H:i:s");
        $currentTimestamp = $this->dateTime->timestamp($currentTime);
        $grandTotalInCents = (int)(round(
            $this->order->getGrandTotal() * 100,
            \Sezzle\Payment\Model\Api\PayloadBuilder::PRECISION
        ));
        $sezzleOrderInfo = $this->sezzle
                            ->getSezzleOrderInfo($reference);

        if (isset($sezzleOrderInfo['amount_in_cents'])
            && ($grandTotalInCents != $sezzleOrderInfo['amount_in_cents'])) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Capture request has been rejected due to invalid order total.')
            );
        } elseif ($captureExpirationTimestamp >= $currentTimestamp) {
            $hasSezzleCaptured = $this->sezzle
                                        ->sezzleCapture($reference);
            if ($hasSezzleCaptured) {
                $invoice->setRequestedCaptureCase(self::CAPTURE_ONLINE);
            }
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(self::CAPTURE_ERROR_MSG)
            );
        }
    }
}
