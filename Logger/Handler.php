<?php

namespace Sezzle\Sezzlepay\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Class Handler
 * @package Sezzle\Sezzlepay\Logger
 */
class Handler extends Base
{
    protected $loggerType = Logger::INFO;

    protected $fileName = '/var/log/sezzlepay.log';
}
