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
use Sezzle\Sezzlepay\Gateway\Config\Config;

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
     * @var Config
     */
    private $config;

    /**
     * @var File
     */
    private $file;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * SezzleLog constructor.
     * @param Config $config
     * @param File $file
     * @param V1Interface $v1
     * @param Data $helper
     * @param StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        Config                   $config,
        File                     $file,
        V1Interface              $v1,
        Data                     $helper,
        StoreRepositoryInterface $storeRepository
    )
    {
        $this->config = $config;
        $this->file = $file;
        $this->v1 = $v1;
        $this->helper = $helper;
        $this->storeRepository = $storeRepository;
    }

    /**
     * Jobs for sending sezzle.log to Sezzle
     * @throws LocalizedException
     */
    public function execute()
    {
        foreach ($this->storeRepository->getList() as $store) {
            $isLogsSendingToSezzleAllowed = $this->config->isLogsSendingToSezzleAllowed($store->getId());
            $isProductionMode = $this->config->getPaymentMode($store->getId()) === Config::PAYMENT_MODE_LIVE;
            if (!($isLogsSendingToSezzleAllowed && $isProductionMode)) {
                return;
            }

            $this->helper->logSezzleActions("****Cron started****");
            $merchantUUID = $this->config->getMerchantUUID();
            $this->helper->logSezzleActions("Merchant UUID : $merchantUUID");
            $logContents = $this->file->fileGetContents(BP . Data::SEZZLE_LOG_FILE_PATH);
            $this->v1->sendLogsToSezzle($merchantUUID, $logContents, $store->getId());
            $this->helper->logSezzleActions("****Cron end****");

        }
    }
}
