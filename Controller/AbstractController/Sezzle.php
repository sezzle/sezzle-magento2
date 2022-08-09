<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Controller\AbstractController;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\Tokenize;
use Sezzle\Sezzlepay\Api\CartManagementInterface;
use Sezzle\Sezzlepay\Api\GuestCartManagementInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;

/**
 * Class Sezzle
 * @package Sezzle\Sezzlepay\Controller\AbstractController
 */
abstract class Sezzle implements HttpGetActionInterface
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
     * @var Data
     */
    protected $helper;
    /**
     * @var Tokenize
     */
    protected $tokenize;
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
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * Sezzle constructor.
     * @param RequestInterface $request
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param OrderFactory $orderFactory
     * @param Data $helper
     * @param Tokenize $tokenize
     * @param ManagerInterface $messageManager
     * @param RedirectFactory $resultRedirectFactory
     * @param QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteIdInterface
     * @param CartManagementInterface $cartManagement
     * @param GuestCartManagementInterface $guestCartManagement
     */
    public function __construct(
        RequestInterface                $request,
        CustomerSession                 $customerSession,
        CheckoutSession                 $checkoutSession,
        OrderFactory                    $orderFactory,
        Data                            $helper,
        Tokenize                        $tokenize,
        ManagerInterface                $messageManager,
        RedirectFactory                 $resultRedirectFactory,
        QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteIdInterface,
        CartManagementInterface         $cartManagement,
        GuestCartManagementInterface    $guestCartManagement
    )
    {
        $this->request = $request;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->helper = $helper;
        $this->tokenize = $tokenize;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->quoteIdToMaskedQuoteIdInterface = $quoteIdToMaskedQuoteIdInterface;
        $this->cartManagement = $cartManagement;
        $this->guestCartManagement = $guestCartManagement;
    }

    /**
     * Get Order
     *
     * @return Order
     */
    protected function getOrder(): Order
    {
        return $this->orderFactory->create()->loadByIncrementId(
            $this->checkoutSession->getLastRealOrderId()
        );
    }
}
