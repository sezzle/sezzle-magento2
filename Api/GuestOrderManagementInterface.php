<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Api;

use Exception;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
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
     */
    public function createCheckout($cartId, $email, $createSezzleCheckout);

    /**
     * Place Order
     *
     * @param string $cartId
     * @return bool
     */
    public function placeOrder($cartId);

}
