<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Cron;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;
use Sezzle\Sezzlepay\Api\V1Interface;
use Sezzle\Sezzlepay\Helper\Data;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleConfigInterface;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleIdentity;

/**
 * Class SezzleLog
 * @package Sezzle\Sezzlepay\Model\Cron
 */
class SezzleLog
{

    /**
     * @var V1Interface
     */
    private $v1;
    /**
     * @var SezzleConfigInterface
     */
    private $sezzleConfig;
    /**
     * @var File
     */
    private $file;
    /**
     * @var Data
     */
    private $sezzleHelper;

    /**
     * SezzleLog constructor.
     * @param SezzleConfigInterface $sezzleConfig
     * @param File $file
     * @param V1Interface $v1
     * @param Data $sezzleHelper
     */
    public function __construct(
        SezzleConfigInterface $sezzleConfig,
        File $file,
        V1Interface $v1,
        Data $sezzleHelper
    ) {
        $this->sezzleConfig = $sezzleConfig;
        $this->file = $file;
        $this->v1 = $v1;
        $this->sezzleHelper = $sezzleHelper;
    }

    /**
     * Jobs for sending sezzle.log to Sezzle
     * @throws LocalizedException
     */
    public function execute()
    {
        $isLogsSendingToSezzleAllowed = $this->sezzleConfig->isLogsSendingToSezzleAllowed();
        $isProductionMode = $this->sezzleConfig->getPaymentMode() == SezzleIdentity::PROD_MODE;
        if ($isLogsSendingToSezzleAllowed && $isProductionMode) {
            $this->sezzleHelper->logSezzleActions("****Cron started****");
            $merchantUUID = $this->sezzleConfig->getMerchantUUID();
            $this->sezzleHelper->logSezzleActions("Merchant UUID : $merchantUUID");
            $logContents = $this->file->fileGetContents(BP . Data::SEZZLE_LOG_FILE_PATH);
            $this->v1->sendLogsToSezzle($merchantUUID, $logContents);
            $this->sezzleHelper->logSezzleActions("****Cron end****");
        }
    }
}
