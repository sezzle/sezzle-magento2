<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Order;

use Exception;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteManagement;
use Magento\Quote\Model\QuoteValidator;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Sezzle\Sezzlepay\Model\AddressValidator;
use Sezzle\Sezzlepay\Model\Api\PayloadBuilder;
use Sezzle\Sezzlepay\Model\Sezzle;
use Sezzle\Sezzlepay\Model\Tokenize;

/**
 * Class SaveHandler
 * @package Sezzle\Sezzlepay\Model\Order
 */
class SaveHandler
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
     * @var UrlInterface
     */
    private $url;
    /**
     * @var QuoteValidator
     */
    private $quoteValidator;
    /**
     * @var AddressValidator
     */
    private $addressValidator;

    /**
     * SaveHandler constructor.
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
     * @param UrlInterface $url
     * @param QuoteValidator $quoteValidator
     * @param AddressValidator $addressValidator
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
        UrlInterface $url,
        QuoteValidator $quoteValidator,
        AddressValidator $addressValidator
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
        $this->url = $url;
        $this->quoteValidator = $quoteValidator;
        $this->addressValidator = $addressValidator;
    }

    /**
     * Start Sezzle Checkout
     *
     * @param Quote $quote
     * @param bool $createSezzleCheckout
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws NotFoundException
     */
    public function createCheckout($quote, $createSezzleCheckout)
    {
        $this->sezzleHelper->logSezzleActions("****Starting Sezzle Checkout****");
        $this->sezzleHelper->logSezzleActions("Quote Id : " . $quote->getId());
        $this->sezzleHelper->logSezzleActions("Customer Id : " . $quote->getCustomer()->getId());

        $this->addressValidator->validateAddress($quote);

        $payment = $quote->getPayment();
        $payment->setMethod(Sezzle::PAYMENT_CODE);
        $quote->reserveOrderId();
        $this->sezzleHelper->logSezzleActions("Order ID from quote : " . $quote->getReservedOrderId());
        $referenceID = uniqid() . "-" . $quote->getReservedOrderId();
        $additionalInformation[Sezzle::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID] = $referenceID;
        $payment->setAdditionalInformation($additionalInformation);
        $quote->setPayment($payment);
        $this->quoteValidator->validateBeforeSubmit($quote);
        $this->cartRepository->save($quote);
        $this->checkoutSession->replaceQuote($quote);
        if ($createSezzleCheckout) {
            $checkoutUrl = $this->sezzleModel->getSezzleRedirectUrl($quote);
            if ($quote->getPayment()->getAdditionalInformation(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID)) {
                $orderId = $this->save($quote);
                if (!$orderId) {
                    throw new CouldNotSaveException(__("Unable to place your order."));
                }
                $successURL = $this->url->getUrl("checkout/onepage/success");
                return $this->jsonHelper->jsonEncode(["checkout_url" => $successURL]);
            }
            return $this->jsonHelper->jsonEncode(["checkout_url" => $checkoutUrl]);
        }
        $payload = $this->apiPayloadBuilder->buildSezzleCheckoutPayload($quote, $referenceID);
        return $this->jsonHelper->jsonEncode(["payload" => $payload]);
    }

    /**
     * Save Order
     *
     * @param Quote $quote
     * @return int
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException|LocalizedException
     */
    public function save($quote)
    {
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
                throw new CouldNotSaveException(
                    __($e->getMessage()),
                    $e
                );
            }
        }
        return $order->getId();
    }
}
