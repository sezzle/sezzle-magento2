<?php

namespace Sezzle\Sezzlepay\Model\Config\Container;

use Magento\Store\Model\Store;

/**
 * Interface IdentityInterface
 * @package Sezzle\Sezzlepay\Model\Config\Container
 */
interface SezzleApiConfigInterface extends IdentityInterface
{

    /**
     * @return mixed
     */
    public function getPublicKey();

    /**
     * @return mixed
     */
    public function getPrivateKey();

    /**
     * @return mixed
     */
    public function getApiMode();

    /**
     * @return mixed
     */
    public function getMerchantId();

    /**
     * @return mixed
     */
    public function getSezzleBaseUrl();
}
