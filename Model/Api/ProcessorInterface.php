<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model\Api;

use Magento\Framework\Http\ZendClient;

/**
 * Interface ProcessorInterface
 * @package Sezzle\Payment\Model\Api
 */
interface ProcessorInterface
{
    const BAD_REQUEST = 400;

    /**
     * Call to Sezzle Gateway
     *
     * @param $url
     * @param $authToken
     * @param bool $body
     * @param $method
     * @return mixed
     */
    public function call(
        $url,
        $authToken = null,
        $body = false,
        $method = ZendClient::GET
    );
}
