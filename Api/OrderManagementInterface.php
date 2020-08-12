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
 * Interface OrderManagementInterface
 * @package Sezzle\Sezzlepay\Api
 */
interface OrderManagementInterface
{

    /**
     * Create Checkout
     *
     * @param int $cartId
     * @return void
     */
    public function createCheckout($cartId);

    /**
     * Place Order
     *
     * @param $cartId
     * @return bool
     */
    public function placeOrder($cartId);

}
