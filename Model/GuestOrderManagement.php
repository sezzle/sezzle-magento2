<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model;

use Exception;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Sezzle\Sezzlepay\Api\GuestOrderManagementInterface;
use Sezzle\Sezzlepay\Model\Api\PayloadBuilder;

class GuestOrderManagement implements GuestOrderManagementInterface
{

    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
    /**
     * @var OrderFactory
     */
    protected $orderFactory;
    /**
     * @var Sezzle
     */
    protected $sezzleModel;
    /**
     * @var OrderSender
     */
    protected $orderSender;
    /**
     * @var Data
     */
    protected $jsonHelper;
    /**
     * @var QuoteManagement
     */
    protected $quoteManagement;
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Sezzle\Sezzlepay\Helper\Data
     */
    protected $sezzleHelper;
    /**
     * @var Tokenize
     */
    protected $tokenize;
    /**
     * @var CartRepositoryInterface
     */
    protected $cartRepository;
    /**
     * @var PayloadBuilder
     */
    private $apiPayloadBuilder;
    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * Payment constructor.
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param OrderFactory $orderFactory
     * @param Sezzle $sezzleModel
     * @param \Sezzle\Sezzlepay\Helper\Data $sezzleHelper
     * @param JsonFactory $resultJsonFactory
     * @param Data $jsonHelper
     * @param QuoteManagement $quoteManagement
     * @param OrderSender $orderSender
     * @param Tokenize $tokenize
     * @param CartRepositoryInterface $cartRepository
     * @param PayloadBuilder $apiPayloadBuilder
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        OrderFactory $orderFactory,
        Sezzle $sezzleModel,
        \Sezzle\Sezzlepay\Helper\Data $sezzleHelper,
        JsonFactory $resultJsonFactory,
        Data $jsonHelper,
        QuoteManagement $quoteManagement,
        OrderSender $orderSender,
        Tokenize $tokenize,
        CartRepositoryInterface $cartRepository,
        PayloadBuilder $apiPayloadBuilder,
        QuoteIdMaskFactory $quoteIdMaskFactory
    ) {
        $this->customerSession = $customerSession;
        $this->sezzleHelper = $sezzleHelper;
        $this->jsonHelper = $jsonHelper;
        $this->customerRepository = $customerRepository;
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->sezzleModel = $sezzleModel;
        $this->quoteManagement = $quoteManagement;
        $this->orderSender = $orderSender;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->tokenize = $tokenize;
        $this->cartRepository = $cartRepository;
        $this->apiPayloadBuilder = $apiPayloadBuilder;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * @inheritDoc
     */
    public function createCheckout($cartId, $email, $createSezzleCheckout)
    {
        $this->sezzleHelper->logSezzleActions("****Starting Sezzle Checkout****");
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->cartRepository->getActive($quoteIdMask->getQuoteId());
        if (!$quote || !$email) {
            return false;
        }
        $this->sezzleHelper->logSezzleActions("Quote Id : " . $quote->getId());
        $this->sezzleHelper->logSezzleActions("Order ID from quote : " . $quote->getReservedOrderId());
        $this->sezzleHelper->logSezzleActions("Guest customer");
        $quote->setCustomerEmail($email)
            ->setCustomerIsGuest(true)
            ->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
        $payment = $quote->getPayment();
        $payment->setMethod(Sezzle::PAYMENT_CODE);
        $quote->reserveOrderId();
        $referenceID = uniqid() . "-" . $quote->getReservedOrderId();
        $additionalInformation[Sezzle::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID] = $referenceID;
        $payment->setAdditionalInformation($additionalInformation);
        $quote->setPayment($payment);
        $this->cartRepository->save($quote);
        $this->checkoutSession->replaceQuote($quote);
        if ($createSezzleCheckout) {
            $checkoutUrl = $this->sezzleModel->getSezzleRedirectUrl($quote);
            return $this->jsonHelper->jsonEncode(["checkout_url" => $checkoutUrl]);
        }
        $payload = $this->apiPayloadBuilder->buildSezzleCheckoutPayload($quote, $referenceID);
        return $this->jsonHelper->jsonEncode(["payload" => $payload]);
    }

    /**
     * @inheritDoc
     */
    public function placeOrder($cartId)
    {
        try {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $this->cartRepository->getActive($quoteIdMask->getQuoteId());
            if (!$quote) {
                return false;
            }
            $this->sezzleHelper->logSezzleActions("Returned from Sezzle.");
            $orderId = $quote->getReservedOrderId();
            $this->sezzleHelper->logSezzleActions("Order ID from quote : $orderId.");

            $this->checkoutSession
                ->setLastQuoteId($quote->getId())
                ->setLastSuccessQuoteId($quote->getId())
                ->clearHelperData();
            $this->sezzleHelper->logSezzleActions("Set data on checkout session");

            $quote->collectTotals();
            /** @var Order $order */
            $order = $this->quoteManagement->submit($quote);
            if ($order) {
                $this->sezzleHelper->logSezzleActions("Order created");
                $this->checkoutSession->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());
                // send email
                try {
                    $this->orderSender->send($order);
                } catch (Exception $e) {
                    $this->sezzleHelper->logSezzleActions("Transaction Email Sending Error: ");
                    $this->sezzleHelper->logSezzleActions($e->getMessage());
                }
            }
        } catch (LocalizedException $e) {
            $this->sezzleHelper->logSezzleActions("Transaction Exception: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            $this->sezzleHelper->logSezzleActions("Transaction Exception: " . $e->getMessage());
            return false;
        }
        return true;
    }
}
