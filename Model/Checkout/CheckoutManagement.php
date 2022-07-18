<?php

namespace Sezzle\Sezzlepay\Model\Checkout;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Sezzle\Sezzlepay\Api\CheckoutInterface;
use Sezzle\Sezzlepay\Api\CheckoutManagementInterface;

class CheckoutManagement implements CheckoutManagementInterface
{

    /**
     * @var PaymentInformationManagementInterface
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
     * CheckoutManagement constructor.
     * @param PaymentInformationManagementInterface $paymentInformationManagement
     * @param CheckoutInterface $checkout
     * @param Json $jsonSerializer
     */
    public function __construct(
        PaymentInformationManagementInterface $paymentInformationManagement,
        CheckoutInterface                     $checkout,
        Json                                  $jsonSerializer
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
        int              $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null): string
    {
        if (!$this->paymentInformationManagement->savePaymentInformation(
            $cartId,
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
