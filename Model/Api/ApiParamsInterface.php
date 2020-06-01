<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model\Api;

use Magento\Framework\Http\ZendClient;


/**
 * Interface ApiParamsInterface
 * @package Sezzle\Payment\Model\Api
 */
interface ApiParamsInterface
{
    const CONTENT_TYPE_JSON = "application/json";
    const CONTENT_TYPE_XML = "application/xml";
    const TIMEOUT = 80;

}
