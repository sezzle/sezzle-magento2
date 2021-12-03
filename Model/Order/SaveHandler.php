<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Order;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartManagementInterface;
use Sezzle\Sezzlepay\Model\CheckoutValidator;
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
     * @var Sezzle
     */
    protected $sezzleModel;
    /**
     * @var Data
     */
    protected $jsonHelper;

    /**
     * @var \Sezzle\Sezzlepay\Helper\Data
     */
    protected $sezzleHelper;
    /**
     * @var UrlInterface
     */
    private $url;
    /**
     * @var CheckoutValidator
     */
    private $checkoutValidator;
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * SaveHandler constructor.
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param Sezzle $sezzleModel
     * @param \Sezzle\Sezzlepay\Helper\Data $sezzleHelper
     * @param Data $jsonHelper
     * @param UrlInterface $url
     * @param CheckoutValidator $checkoutValidator
     * @param CartManagementInterface $cartManagement
     * @param ProductMetadataInterface $productMetadata
     */
    public function __construct(
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        Sezzle $sezzleModel,
        \Sezzle\Sezzlepay\Helper\Data $sezzleHelper,
        Data $jsonHelper,
        UrlInterface $url,
        CheckoutValidator $checkoutValidator,
        CartManagementInterface $cartManagement,
        ProductMetadataInterface $productMetadata
    ) {
        $this->customerSession = $customerSession;
        $this->sezzleHelper = $sezzleHelper;
        $this->jsonHelper = $jsonHelper;
        $this->checkoutSession = $checkoutSession;
        $this->sezzleModel = $sezzleModel;
        $this->url = $url;
        $this->checkoutValidator = $checkoutValidator;
        $this->cartManagement = $cartManagement;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Start Sezzle Checkout
     *
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function createCheckout()
    {
        $quote = $this->checkoutSession->getQuote();
        $magentoVersion = $this->productMetadata->getEdition() . " " . $this->productMetadata->getVersion();
        $sezzleVersion = $this->sezzleHelper->getVersion();
        $this->sezzleHelper->logSezzleActions(sprintf("Magento Version : %s | Sezzle Version : %s", $magentoVersion, $sezzleVersion));
        $this->sezzleHelper->logSezzleActions("****Starting Sezzle Checkout****");
        $this->sezzleHelper->logSezzleActions("Quote Id : " . $quote->getId());
        $this->sezzleHelper->logSezzleActions("Customer Id : " . $quote->getCustomer()->getId());

        $this->checkoutValidator->validate($quote);

        $payment = $quote->getPayment();
        $quote->reserveOrderId();
        $this->sezzleHelper->logSezzleActions("Order ID from quote : " . $quote->getReservedOrderId());
        $referenceID = uniqid() . "-" . $quote->getReservedOrderId();
        $additionalInformation[Sezzle::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID] = $referenceID;
        $payment->setAdditionalInformation($additionalInformation);
        $quote->setPayment($payment);

        $checkoutUrl = $this->sezzleModel->getSezzleRedirectUrl($quote);
        if (!$quote->getPayment()->getAdditionalInformation(Tokenize::ATTR_SEZZLE_CUSTOMER_UUID)) {
            return $this->jsonHelper->jsonEncode(["checkout_url" => $checkoutUrl]);
        }

        $orderId = $this->cartManagement->placeOrder($quote->getId());
        if (!$orderId) {
            throw new CouldNotSaveException(__("Unable to place your order."));
        }
        $successURL = $this->url->getUrl("checkout/onepage/success");
        return $this->jsonHelper->jsonEncode(["checkout_url" => $successURL]);
    }
}
