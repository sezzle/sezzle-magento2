<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Api;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Interface CheckoutManagementInterface
 * @package Sezzle\Sezzlepay\Api
 */
interface CheckoutManagementInterface
{

    /**
     * Creates checkout session at Sezzle
     *
     * @param int $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return string
     * @throws CouldNotSaveException
     * @throws NotFoundException
     */
    public function createCheckout(
        int              $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ): string;
}
