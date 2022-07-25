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
     * GuestCheckoutManagement constructor.
     * @param GuestPaymentInformationManagementInterface $paymentInformationManagement
     * @param CheckoutInterface $checkout
     * @param Json $jsonSerializer
     * @param Data $helper
     */
    public function __construct(
        GuestPaymentInformationManagementInterface $paymentInformationManagement,
        CheckoutInterface                          $checkout,
        Json                                       $jsonSerializer,
        Data                                       $helper
    )
    {
        $this->paymentInformationManagement = $paymentInformationManagement;
        $this->checkout = $checkout;
        $this->jsonSerializer = $jsonSerializer;
        $this->helper = $helper;
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
        $log = [
            'quote_id' => $cartId,
            'log_origin' => __METHOD__
        ];

        if (!$this->paymentInformationManagement->savePaymentInformation(
            $cartId,
            $email,
            $paymentMethod,
            $billingAddress
        )) {
            throw new CouldNotSaveException(__("Unable to save payment information."));
        }

        $checkoutURL = $this->checkout->getCheckoutURL();

        $log['checkout_url'] = $checkoutURL;

        if (!$checkoutURL) {
            $this->helper->logSezzleActions($log);

            throw new NotFoundException(__('Checkout URL not found.'));
        }

        $this->helper->logSezzleActions($log);

        return $this->jsonSerializer->serialize(["checkout_url" => $checkoutURL]);
    }
}
