<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model\Api;


/**
 * Interface ConfigInterface
 * @package Sezzle\Payment\Model\Api
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
