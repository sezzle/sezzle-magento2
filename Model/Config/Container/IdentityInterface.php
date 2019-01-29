<?php

namespace Sezzle\Sezzlepay\Model\Config\Container;

use Magento\Store\Model\Store;

/**
 * Interface IdentityInterface
 * @package Sezzle\Sezzlepay\Model\Config\Container
 */
interface IdentityInterface
{
    /**
     * @return bool
     */
    public function isEnabled();

    /**
     * @return Store
     */
    public function getStore();

    /**
     * @param Store $store
     * @return mixed
     */
    public function setStore(Store $store);
}
