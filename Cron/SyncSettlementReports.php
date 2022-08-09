<?php
/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Cron;

use Exception;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Sezzle\Sezzlepay\Api\SettlementReportsManagementInterface;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * Class SyncSettlementReports
 * @package Sezzle\Sezzlepay\Cron
 */
class SyncSettlementReports
{
    /**
     * @var SettlementReportsManagementInterface
     */
    private $settlementReportsManagement;
    /**
     * @var Data
     */
    private $helper;

    /**
     * Constructor
     *
     * @param Data $helper
     * @param SettlementReportsManagementInterface $settlementReportsManagement
     */
    public function __construct(
        Data                                 $helper,
        SettlementReportsManagementInterface $settlementReportsManagement
    )
    {
        $this->helper = $helper;
        $this->settlementReportsManagement = $settlementReportsManagement;
    }

    /**
     * Settlement Reports Sync
     */
    public function execute()
    {
        try {
            $this->helper->logSezzleActions("****Reports syncing started****");
            $this->settlementReportsManagement->syncAndSave();
            $this->helper->logSezzleActions("****Reports syncing ended****");
        } catch (Exception $e) {
            $this->helper->logSezzleActions(sprintf('Report sync error(%s): %s', get_class($e), $e->getMessage()));
        }
    }
}
