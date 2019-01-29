<?php
/**
 * Created by PhpStorm.
 * User: arijit
 * Date: 1/29/2019
 * Time: 11:03 PM
 */

namespace Sezzle\Sezzlepay\Model\Api;

use Magento\Framework\Http\ZendClient;


interface ApiParamsInterface
{
    const CONTENT_TYPE_JSON = "application/json";
    const CONTENT_TYPE_XML = "application/xml";
    const TIMEOUT = 80;

}