<?php

namespace Sezzle\Sezzlepay\Model\Api;

use Magento\Framework\Http\ZendClient;


interface ProcessorInterface
{
    public function call($url, $body = false, $method = ZendClient::GET);

}