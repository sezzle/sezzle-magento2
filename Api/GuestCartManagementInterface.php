<?php

namespace Sezzle\Sezzlepay\Api;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Cart Management interface for guest carts.
 */
interface GuestCartManagementInterface
{

    /**
     * Place an order for a specified cart.
     *
     * @param string $cartId The cart ID.
     * @param PaymentInterface|null $paymentMethod
     * @return int Order ID.
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException|LocalizedException
     */
    public function placeOrder(string $cartId, PaymentInterface $paymentMethod = null): int;
}
