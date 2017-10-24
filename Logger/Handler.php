<?php
namespace Sezzle\Pay\Logger;
//use Monolog\Logger as Logger;
class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/sezzle-pay.log';
}