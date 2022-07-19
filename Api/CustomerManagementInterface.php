<?php

namespace Sezzle\Sezzlepay\Api;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

interface CustomerManagementInterface
{

    /**
     * Creates order by customer UUID at Sezzle
     *
     * @param int $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return string
     * @throws CouldNotSaveException
     * @throws NotFoundException
     */
    public function createOrder(
        int              $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null): string;

}
