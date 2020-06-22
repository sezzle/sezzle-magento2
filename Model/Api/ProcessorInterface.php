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
    /**
     * Call to Sezzle Gateway
     *
     * @param string $url
     * @param string $authToken
     * @param bool|array $body
     * @param string $method
     * @return mixed
     */
    public function call(
        $url,
        $authToken = null,
        $body = false,
        $method = ZendClient::GET
    );
}
