<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Api;

use Magento\Framework\Http\ZendClient;

/**
 * Interface ProcessorInterface
 * @package Sezzle\Sezzlepay\Model\Api
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
     * @param bool $getResponseStatusCode
     * @return array|string
     */
    public function call(
        $url,
        $authToken = null,
        $body = false,
        $method = ZendClient::GET,
        $getResponseStatusCode = false
    );
}
