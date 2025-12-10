<?php

namespace Sezzle\Sezzlepay\Logger;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Magento\Framework\Filesystem\DriverInterface;

/**
 * Class Handler
 * @package Sezzle\Sezzlepay\Logger
 */
class Handler extends RotatingFileHandler
{
    /**
     * Maximum number of log files to keep (0 = unlimited)
     * Default: 30 days
     */
    const MAX_FILES = 30;

    /**
     * @param DriverInterface $filesystem
     * @param string|null $filePath
     * @param int $maxFiles Maximum number of files to keep (0 = unlimited)
     * @param int $level The minimum logging level
     * @param bool $bubble Whether to bubble messages
     */
    public function __construct(
        DriverInterface $filesystem,
        ?string $filePath = null,
        int $maxFiles = self::MAX_FILES,
        int $level = Logger::INFO,
        bool $bubble = true
    ) {
        $filePath = $filePath ?: BP . '/var/log/sezzlepay.log';
        parent::__construct($filePath, $maxFiles, $level, $bubble);
    }
}
