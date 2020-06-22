<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model\System\Config\Container;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;

/**
 * Interface IdentityInterface
 * @package Sezzle\Payment\Model\System\Config\Container
 */
interface IdentityInterface
{
    /**
     * Check if payment method is enabled
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isEnabled();

    /**
     * Set store
     * @return Store
     */
    public function getStore();

    /**
     * Get Store
     * @param Store $store
     * @return mixed
     */
    public function setStore(Store $store);
}
