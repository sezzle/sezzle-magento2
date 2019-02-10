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
 * Interface ApiParamsInterface
 * @package Sezzle\Sezzlepay\Model\Api
 */
interface ApiParamsInterface
{
    const CONTENT_TYPE_JSON = "application/json";
    const CONTENT_TYPE_XML = "application/xml";
    const TIMEOUT = 80;

}