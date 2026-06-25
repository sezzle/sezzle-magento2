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
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Helper\Util;
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
     * @var CartRepositoryInterface
     */
    protected $cartRepository;
    /**
     * @var V2Interface
     */
    protected $v2;

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
     * @param CartRepositoryInterface $cartRepository
     * @param V2Interface $v2
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
        GuestCartManagementInterface    $guestCartManagement,
        CartRepositoryInterface         $cartRepository,
        V2Interface                     $v2
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
        $this->cartRepository = $cartRepository;
        $this->v2 = $v2;
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

    /**
     * Best-effort release of a Sezzle authorization left stranded when the Magento order
     * could not be created. Never interrupts the response flow.
     *
     * @param CartInterface $quote
     * @return void
     */
    protected function releaseStrandedAuthorization(CartInterface $quote): void
    {
        try {
            $payment = $quote->getPayment();
            $orderUUID = $payment
                ? $payment->getAdditionalInformation(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID)
                : null;
            if (!$orderUUID) {
                return;
            }

            $this->v2->releasePayment(
                $orderUUID,
                Util::formatToCents($quote->getBaseGrandTotal()),
                (string)$quote->getBaseCurrencyCode(),
                (int)$quote->getStoreId()
            );
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Released stranded Sezzle authorization after failed order creation',
                'order_uuid' => $orderUUID
            ]);
        } catch (\Exception $e) {
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Failed to release stranded Sezzle authorization',
                'error' => $e->getMessage()
            ]);
        }
    }
}
