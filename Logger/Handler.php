<?php

namespace Sezzle\Sezzlepay\Logger;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

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
     * @param string|null $filePath Base log file path (RotatingFileHandler will append date: sezzlepay-YYYY-MM-DD.log)
     * @param int $maxFiles Maximum number of files to keep (0 = unlimited)
     * @param int $level The minimum logging level
     * @param bool $bubble Whether to bubble messages
     */
    public function __construct(
        ?string $filePath = null,
        int $maxFiles = self::MAX_FILES,
        int $level = Logger::INFO,
        bool $bubble = true
    ) {
        // RotatingFileHandler automatically creates date-based files: sezzlepay-YYYY-MM-DD.log
        $filePath = $filePath ?: BP . '/var/log/sezzlepay.log';
        parent::__construct($filePath, $maxFiles, $level, $bubble);
    }
}
