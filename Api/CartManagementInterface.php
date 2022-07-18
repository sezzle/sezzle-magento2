<?php

namespace Sezzle\Sezzlepay\Api;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Interface CartManagementInterface
 */
interface CartManagementInterface
{
    /**
     * Checkout types: Checkout as Guest
     */
    const METHOD_GUEST = 'guest';

    /**
     * Places an order for a specified cart.
     *
     * @param int $cartId The cart ID.
     * @param PaymentInterface|null $paymentMethod
     * @return int Order ID.
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException|LocalizedException
     */
    public function placeOrder(int $cartId, PaymentInterface $paymentMethod = null): int;
}
