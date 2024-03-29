<?php

namespace Sezzle\Sezzlepay\Model\Tokenize;

use Exception;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Sezzle\Sezzlepay\Api\CheckoutInterface;
use Sezzle\Sezzlepay\Api\CustomerInterface;
use Sezzle\Sezzlepay\Api\CustomerManagementInterface;
use Sezzle\Sezzlepay\Helper\Data;

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
     * @var Data
     */
    private $helper;

    /**
     * CustomerManagement constructor.
     * @param PaymentInformationManagementInterface $paymentInformationManagement
     * @param CheckoutInterface $checkout
     * @param Json $jsonSerializer
     * @param CustomerInterface $customer
     * @param Data $helper
     */
    public function __construct(
        PaymentInformationManagementInterface $paymentInformationManagement,
        CheckoutInterface                     $checkout,
        Json                                  $jsonSerializer,
        CustomerInterface                     $customer,
        Data                                  $helper
    )
    {
        $this->paymentInformationManagement = $paymentInformationManagement;
        $this->checkout = $checkout;
        $this->jsonSerializer = $jsonSerializer;
        $this->customer = $customer;
        $this->helper = $helper;
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

        $log = [
            'quote_id' => $cartId,
            'log_origin' => __METHOD__
        ];

        try {
            $this->customer->createOrder($cartId);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), '?id=unauth')) {
                return $this->jsonSerializer->serialize(
                    [
                        'success' => true,
                        'checkout_url' => $e->getMessage(),
                    ]
                );
            }

            // trying to create standard checkout as the tokenized order creation failed
            $checkoutURL = $this->checkout->getCheckoutURL($cartId);

            $log['checkout_url'] = $checkoutURL;

            $this->helper->logSezzleActions($log);

            if (!$checkoutURL) {
                throw new NotFoundException(__('Something went wrong while placing order at Sezzle.'));
            }

            return $this->jsonSerializer->serialize(
                [
                    'success' => true,
                    'checkout_url' => $checkoutURL,
                ]
            );
        }

        $this->helper->logSezzleActions($log);

        return $this->jsonSerializer->serialize(['success' => true]);
    }
}
