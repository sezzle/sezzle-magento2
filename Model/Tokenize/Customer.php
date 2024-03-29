<?php

namespace Sezzle\Sezzlepay\Model\Tokenize;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\ResourceModel\Quote;
use Sezzle\Sezzlepay\Api\CartManagementInterface;
use Sezzle\Sezzlepay\Api\CustomerInterface;
use Sezzle\Sezzlepay\Api\CheckoutInterface;
use Sezzle\Sezzlepay\Gateway\Request\CustomerOrderRequestBuilder;
use Sezzle\Sezzlepay\Model\Tokenize;

class Customer implements CustomerInterface
{

    /**
     * @var Tokenize
     */
    private $tokenize;

    /**
     * @var CheckoutInterface
     */
    private $checkout;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var Quote
     */
    private $quoteResourceModel;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * Customer constructor
     * @param Tokenize $tokenize
     * @param CheckoutInterface $checkout
     * @param CartManagementInterface $cartManagement
     * @param Quote $quoteResourceModel
     * @param Session $checkoutSession
     */
    public function __construct(
        Tokenize                $tokenize,
        CheckoutInterface       $checkout,
        CartManagementInterface $cartManagement,
        Quote                   $quoteResourceModel,
        Session                 $checkoutSession
    )
    {
        $this->tokenize = $tokenize;
        $this->checkout = $checkout;
        $this->cartManagement = $cartManagement;
        $this->quoteResourceModel = $quoteResourceModel;
        $this->checkoutSession = $checkoutSession;
    }


    /**
     * @inheritDoc
     */
    public function createOrder(int $cartId): void
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->checkout->initQuote($cartId);
        if (!$quote->getCustomer() || !$this->tokenize->isCustomerUUIDValid($quote)) {
            throw new Exception(__('Invalid customer.'));
        }

        $quote->getPayment()->setAdditionalInformation([
            Tokenize::ATTR_SEZZLE_CUSTOMER_UUID => $quote->getCustomer()->getCustomAttribute(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID)->getValue(),
            Tokenize::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION => $quote->getCustomer()->getCustomAttribute(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID_EXPIRATION)->getValue(),
            Tokenize::KEY_CREATE_ORDER_LINK => $quote->getCustomer()->getCustomAttribute(Tokenize::KEY_CREATE_ORDER_LINK)->getValue(),
            CustomerOrderRequestBuilder::KEY_REFERENCE_ID => uniqid() . "-" . $quote->getReservedOrderId()
        ]);

        $this->quoteResourceModel->save($quote->collectTotals());
        $this->checkoutSession->replaceQuote($quote);
        $this->cartManagement->placeOrder($quote->getId());
    }
}
