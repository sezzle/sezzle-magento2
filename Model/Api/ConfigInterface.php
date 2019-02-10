<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 * @license     https://www.sezzle.com/LICENSE.txt
 */

namespace Sezzle\Sezzlepay\Model\Api;


/**
 * Interface ConfigInterface
 * @package Sezzle\Sezzlepay\Model\Api
 */
interface ConfigInterface
{
    /**
     * Get auth token
     * @return mixed
     */
    public function getAuthToken();

    /**
     * Get complete url
     * @param $orderId
     * @param $reference
     * @return mixed
     */
    public function getCompleteUrl($orderId, $reference);

    /**
     * Get cancel url
     * @return mixed
     */
    public function getCancelUrl();
}