<?php

namespace Sezzle\Sezzlepay\Model\Checkout;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Sezzle\Sezzlepay\Api\CheckoutInterface;
use Sezzle\Sezzlepay\Api\GuestCheckoutManagementInterface;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * GuestCheckoutManagement
 */
class GuestCheckoutManagement implements GuestCheckoutManagementInterface
{

    /**
     * @var GuestPaymentInformationManagementInterface
     */
    private $paymentInformationManagement;

    /**
     * @var CheckoutInterface
     */
    private $checkout;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * GuestCheckoutManagement constructor.
     * @param GuestPaymentInformationManagementInterface $paymentInformationManagement
     * @param CheckoutInterface $checkout
     * @param Json $jsonSerializer
     * @param Data $helper
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     */
    public function __construct(
        GuestPaymentInformationManagementInterface $paymentInformationManagement,
        CheckoutInterface                          $checkout,
        Json                                       $jsonSerializer,
        Data                                       $helper,
        QuoteIdMaskFactory       $quoteIdMaskFactory
    )
    {
        $this->paymentInformationManagement = $paymentInformationManagement;
        $this->checkout = $checkout;
        $this->jsonSerializer = $jsonSerializer;
        $this->helper = $helper;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
    }

    /**
     * @inheritDoc
     */
    public function createCheckout(
        string           $cartId,
        string           $email,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null): string
    {
        if (!$this->paymentInformationManagement->savePaymentInformation(
            $cartId,
            $email,
            $paymentMethod,
            $billingAddress
        )) {
            throw new CouldNotSaveException(__("Unable to save payment information."));
        }

        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        $checkoutURL = $this->checkout->getCheckoutURL($quoteIdMask->getQuoteId());

        $this->helper->logSezzleActions([
            'quote_id' => $cartId,
            'log_origin' => __METHOD__,
            'checkout_url' => $checkoutURL
        ]);

        if (!$checkoutURL) {
            throw new NotFoundException(__('Checkout URL not found.'));
        }

        return $this->jsonSerializer->serialize(["checkout_url" => $checkoutURL]);
    }
}
