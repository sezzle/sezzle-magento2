<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Controller\AbstractController;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Json\Helper\Data;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\OrderFactory;
use Sezzle\Sezzlepay\Model\Tokenize;
use Sezzle\Sezzlepay\Api\CartManagementInterface;
use Sezzle\Sezzlepay\Api\GuestCartManagementInterface;

/**
 * Class Sezzle
 * @package Sezzle\Sezzlepay\Controller\AbstractController
 */
abstract class Sezzle extends Action
{
    const GUEST_CART_MANAGER = "guestCartManagement";
    const CART_MANAGER = "cartManagement";
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
     * @var \Sezzle\Sezzlepay\Model\Sezzle
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
     * @var CartManagementInterface
     */
    protected $cartManagement;
    /**
     * @var GuestCartManagementInterface
     */
    protected $guestCartManagement;
    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    protected $quoteIdToMaskedQuoteIdInterface;

    /**
     * @var CartManagementInterface
     */
    protected $sezzleCartManagement;

    /**
     * Payment constructor.
     * @param Context $context
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param OrderFactory $orderFactory
     * @param \Sezzle\Sezzlepay\Model\Sezzle $sezzleModel
     * @param \Sezzle\Sezzlepay\Helper\Data $sezzleHelper
     * @param JsonFactory $resultJsonFactory
     * @param Data $jsonHelper
     * @param QuoteManagement $quoteManagement
     * @param OrderSender $orderSender
     * @param Tokenize $tokenize
     * @param CartRepositoryInterface $cartRepository
     * @param CartManagementInterface $cartManagement
     * @param GuestCartManagementInterface $guestCartManagement
     * @param QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteIdInterface
     */
    public function __construct(
        Context                                            $context,
        CustomerRepositoryInterface                        $customerRepository,
        CustomerSession                                    $customerSession,
        CheckoutSession                                    $checkoutSession,
        OrderFactory                                       $orderFactory,
        \Sezzle\Sezzlepay\Model\Sezzle                     $sezzleModel,
        \Sezzle\Sezzlepay\Helper\Data   $sezzleHelper,
        JsonFactory                     $resultJsonFactory,
        Data                            $jsonHelper,
        QuoteManagement                 $quoteManagement,
        OrderSender                     $orderSender,
        Tokenize                        $tokenize,
        CartRepositoryInterface         $cartRepository,
        QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteIdInterface,
        CartManagementInterface         $cartManagement,
        GuestCartManagementInterface    $guestCartManagement
    )
    {
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
        $this->quoteIdToMaskedQuoteIdInterface = $quoteIdToMaskedQuoteIdInterface;
        $this->cartManagement = $cartManagement;
        $this->guestCartManagement = $guestCartManagement;
        parent::__construct($context);
    }

    /**
     * Get Order
     *
     * @return Order
     */
    protected function getOrder()
    {
        return $this->orderFactory->create()->loadByIncrementId(
            $this->checkoutSession->getLastRealOrderId()
        );
    }
}
