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
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Interface OrderManagementInterface
 * @package Sezzle\Sezzlepay\Api
 */
interface OrderManagementInterface
{

    /**
     * Create Checkout
     *
     * @param bool $createSezzleCheckout
     * @return string
     */
    public function createCheckout($createSezzleCheckout);
}
