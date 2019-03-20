<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Config\Container;

use Magento\Store\Model\Store;

/**
 * Interface IdentityInterface
 * @package Sezzle\Sezzlepay\Model\Config\Container
 */
interface SezzleApiConfigInterface extends IdentityInterface
{

    /**
     * Get public key
     * @return mixed
     */
    public function getPublicKey();

    /**
     * Get private key
     * @return mixed
     */
    public function getPrivateKey();

    /**
     * Get Api mode
     * @return mixed
     */
    public function getApiMode();

    /**
     * Get Merchant Id
     * @return mixed
     */
    public function getMerchantId();

    /**
     * Get Sezzle base url
     * @return mixed
     */
    public function getSezzleBaseUrl();
}
