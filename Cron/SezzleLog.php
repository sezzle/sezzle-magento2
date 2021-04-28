<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Cron;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Store\Api\StoreRepositoryInterface;
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
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * SezzleLog constructor.
     * @param SezzleConfigInterface $sezzleConfig
     * @param File $file
     * @param V1Interface $v1
     * @param Data $sezzleHelper
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        SezzleConfigInterface $sezzleConfig,
        File $file,
        V1Interface $v1,
        Data $sezzleHelper,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->sezzleConfig = $sezzleConfig;
        $this->file = $file;
        $this->v1 = $v1;
        $this->sezzleHelper = $sezzleHelper;
        $this->storeRepository = $storeRepository;
    }

    /**
     * Jobs for sending sezzle.log to Sezzle
     * @throws LocalizedException
     */
    public function execute()
    {
        foreach ($this->storeRepository->getList() as $store) {
            $isLogsSendingToSezzleAllowed = $this->sezzleConfig->isLogsSendingToSezzleAllowed($store->getId());
            $isProductionMode = $this->sezzleConfig->getPaymentMode($store->getId()) == SezzleIdentity::PROD_MODE;
            if ($isLogsSendingToSezzleAllowed && $isProductionMode) {
                $this->sezzleHelper->logSezzleActions("****Cron started****");
                $merchantUUID = $this->sezzleConfig->getMerchantUUID();
                $this->sezzleHelper->logSezzleActions("Merchant UUID : $merchantUUID");
                $logContents = $this->file->fileGetContents(BP . Data::SEZZLE_LOG_FILE_PATH);
                $this->v1->sendLogsToSezzle($merchantUUID, $logContents, $store->getId());
                $this->sezzleHelper->logSezzleActions("****Cron end****");
            }
        }
    }
}
