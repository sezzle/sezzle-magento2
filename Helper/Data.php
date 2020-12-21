<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleConfigInterface;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;
use Zend_Http_UserAgent_Mobile;

/**
 * Sezzle Helper
 */
class Data extends AbstractHelper
{
    const PRECISION = 2;
    const SEZZLE_LOG_FILE_PATH = '/var/log/sezzlepay.log';
    const SEZZLE_MANUAL_INSTALL_COMPOSER_FILE_PATH = '/app/code/Sezzle/Sezzlepay/composer.json';
    const SEZZLE_COMPOSER_INSTALL_COMPOSER_FILE_PATH = '/vendor/sezzle/sezzlepay/composer.json';

    /**
     * @var SezzleConfigInterface
     */
    private $sezzleConfig;
    /**
     * @var File
     */
    private $file;
    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;

    /**
     * Initialize dependencies.
     *
     * @param Context $context
     * @param File $file
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param SezzleConfigInterface $sezzleConfig
     */
    public function __construct(
        Context $context,
        File $file,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        SezzleConfigInterface $sezzleConfig
    ) {
        $this->file = $file;
        $this->jsonHelper = $jsonHelper;
        $this->sezzleConfig = $sezzleConfig;
        parent::__construct($context);
    }

    /**
     * Dump Sezzle log actions
     *
     * @param string|null $data
     * @return void
     */
    public function logSezzleActions($data = null)
    {
        try {
            if ($this->sezzleConfig->isLogTrackerEnabled()) {
                $writer = new Stream(BP . self::SEZZLE_LOG_FILE_PATH);
                $logger = new Logger();
                $logger->addWriter($writer);
                $logger->info($data);
            }
        } catch (NoSuchEntityException $e) {
        }
    }

    /**
     * Check if Device is Mobile or Tablet
     *
     * @return bool
     */
    public function isMobileOrTablet()
    {
        $userAgent = $this->_httpHeader->getHttpUserAgent();
        return Zend_Http_UserAgent_Mobile::match($userAgent, $_SERVER);
    }

    /**
     * Export CSV string to array
     *
     * @param string $content
     * @return array
     */
    public function csvToArray($content)
    {
        $data = ['header' => [], 'data' => []];
        $summary = [];
        $result = [];

        $lines = str_getcsv($content, "\n");
        foreach ($lines as $index => $line) {
            if ($index == 0 || $index == 2) {
                if ($index == 2) {
                    $summary = $data;
                    unset($data);
                }
                $data['header'] = str_getcsv($line);
            } else {
                $row = array_combine($data['header'], str_getcsv($line));
                $data['data'][] = $row;
            }
        }
        array_push($result, $summary, $data);
        return $result;
    }

    /**
     * Convert string from snake case to title case
     * @param string $name
     * @return string
     */
    public function snakeCaseToTitleCase($name)
    {
        $name = str_replace("_", " ", $name);
        $name = ucwords($name);
        return $name;
    }

    /**
     * Get Sezzle Module Version
     */
    public function getVersion()
    {
        try {
            if ($this->file->isExists(BP . self::SEZZLE_MANUAL_INSTALL_COMPOSER_FILE_PATH)) {
                $composerFilePath = BP . self::SEZZLE_MANUAL_INSTALL_COMPOSER_FILE_PATH;
            } else {
                $composerFilePath = BP . self::SEZZLE_COMPOSER_INSTALL_COMPOSER_FILE_PATH;
            }
            $file = $this->file->fileGetContents($composerFilePath);
            if ($file) {
                $contents = $this->jsonHelper->jsonDecode($file);
                if (is_array($contents) && isset($contents['version'])) {
                    return $contents['version'];
                }
            }
        } catch (FileSystemException $e) {
            $this->logSezzleActions("Module not found");
            return '--';
        }
        return '--';
    }

    /**
     * Get amount in cents
     *
     * @param float $amount
     * @return int
     */
    public function getAmountInCents($amount)
    {
        return (int)(round(
            $amount * 100,
            self::PRECISION
        ));
    }
}
