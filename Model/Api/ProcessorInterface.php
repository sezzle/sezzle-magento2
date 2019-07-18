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
        $method = ZendClient::GET);

}
