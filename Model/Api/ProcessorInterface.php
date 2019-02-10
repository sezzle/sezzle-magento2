<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 * @license     https://www.sezzle.com/LICENSE.txt
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
     * @param $url
     * @param bool $body
     * @param $method
     * @return mixed
     */
    public function call($url, $body = false, $method = ZendClient::GET);

}