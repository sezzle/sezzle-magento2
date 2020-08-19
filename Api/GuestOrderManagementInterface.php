<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Api;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;

/**
 * Interface GuestOrderManagementInterface
 * @package Sezzle\Sezzlepay\Api
 */
interface GuestOrderManagementInterface
{

    /**
     * Create Checkout
     *
     * @param string $cartId
     * @param string $email
     * @param bool $createSezzleCheckout
     * @return string
     * @throws NotFoundException
     * @throws CouldNotSaveException
     */
    public function createCheckout($cartId, $email, $createSezzleCheckout);

    /**
     * Place Order
     *
     * @param string $cartId
     * @return int
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws NotFoundException
     */
    public function placeOrder($cartId);

}
