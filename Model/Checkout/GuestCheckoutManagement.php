<?php

namespace Sezzle\Sezzlepay\Model\Checkout;

use Magento\Checkout\Api\GuestPaymentInformationManagementInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Sezzle\Sezzlepay\Api\CheckoutInterface;
use Sezzle\Sezzlepay\Api\GuestCheckoutManagementInterface;

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
     * GuestCheckoutManagement constructor.
     * @param GuestPaymentInformationManagementInterface $paymentInformationManagement
     * @param CheckoutInterface $checkout
     * @param Json $jsonSerializer
     */
    public function __construct(
        GuestPaymentInformationManagementInterface $paymentInformationManagement,
        CheckoutInterface                          $checkout,
        Json                                       $jsonSerializer
    )
    {
        $this->paymentInformationManagement = $paymentInformationManagement;
        $this->checkout = $checkout;
        $this->jsonSerializer = $jsonSerializer;
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

        if (!$checkoutURL = $this->checkout->getCheckoutURL()) {
            throw new NotFoundException(__('Checkout URL not found.'));
        }

        return $this->jsonSerializer->serialize(["checkout_url" => $checkoutURL]);
    }
}
