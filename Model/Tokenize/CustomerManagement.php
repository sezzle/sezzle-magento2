<?php

namespace Sezzle\Sezzlepay\Model\Tokenize;

use Exception;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Sezzle\Sezzlepay\Api\CheckoutInterface;
use Sezzle\Sezzlepay\Api\CustomerInterface;
use Sezzle\Sezzlepay\Api\CustomerManagementInterface;

class CustomerManagement implements CustomerManagementInterface
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
     * @var CustomerInterface
     */
    private $customer;

    /**
     * CustomerManagement constructor.
     * @param PaymentInformationManagementInterface $paymentInformationManagement
     * @param CheckoutInterface $checkout
     * @param Json $jsonSerializer
     * @param CustomerInterface $customer
     */
    public function __construct(
        PaymentInformationManagementInterface $paymentInformationManagement,
        CheckoutInterface                     $checkout,
        Json                                  $jsonSerializer,
        CustomerInterface                     $customer
    )
    {
        $this->paymentInformationManagement = $paymentInformationManagement;
        $this->checkout = $checkout;
        $this->jsonSerializer = $jsonSerializer;
        $this->customer = $customer;
    }

    /**
     * @inheritDoc
     */
    public function createOrder(
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

        try {
            $this->customer->createOrder();
        } catch (AlreadyExistsException|CouldNotSaveException|NoSuchEntityException|LocalizedException|Exception $e) {
            // trying to create standard checkout as the tokenized order creation failed
            if (!$checkoutURL = $this->checkout->getCheckoutURL()) {
                throw new NotFoundException(__('Something went wrong while placing order at Sezzle.'));
            }

            return $this->jsonSerializer->serialize(
                [
                    'success' => true,
                    'checkout_url' => $checkoutURL,
                ]
            );
        }

        return $this->jsonSerializer->serialize(['success' => true]);
    }
}
