<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File;
use Sezzle\Payment\Model\System\Config\Container\SezzleConfigInterface;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;

/**
 * Sezzle Helper
 */
class Data extends AbstractHelper
{
    const PRECISION = 2;
    const SEZZLE_LOG_FILE_PATH = '/var/log/sezzle.log';
    const SEZZLE_COMPOSER_FILE_PATH = '/app/code/Sezzle/Payment/composer.json';

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
     * @param string|null $msg
     * @return void
     * @throws NoSuchEntityException
     */
    public function logSezzleActions($data = null)
    {
        if ($this->sezzleConfig->isLogTrackerEnabled()) {
            $writer = new Stream(BP . self::SEZZLE_LOG_FILE_PATH);
            $logger = new Logger();
            $logger->addWriter($writer);
            $logger->info($data);
        }
    }

    /**
     * Get Sezzle Module Version
     *
     * @throws FileSystemException
     */
    public function getVersion()
    {
        $file = $this->file->fileGetContents(BP . self::SEZZLE_COMPOSER_FILE_PATH);
        if ($file) {
            $contents = $this->jsonHelper->jsonDecode($file);
            if (is_array($contents) && isset($contents['version'])) {
                return $contents['version'];
            }
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
