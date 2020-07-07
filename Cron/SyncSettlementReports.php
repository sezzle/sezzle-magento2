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
    private $sezzleHelper;

    /**
     * Constructor
     *
     * @param Data $sezzleHelper
     * @param SettlementReportsManagementInterface $settlementReportsManagement
     */
    public function __construct(
        Data $sezzleHelper,
        SettlementReportsManagementInterface $settlementReportsManagement
    ) {
        $this->sezzleHelper = $sezzleHelper;
        $this->settlementReportsManagement = $settlementReportsManagement;
    }

    /**
     * Settlement Reports Sync
     *
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        try {
            $this->sezzleHelper->logSezzleActions("****Reports syncing started****");
            $this->settlementReportsManagement->syncAndSave();
            $this->sezzleHelper->logSezzleActions("****Reports syncing ended****");
        } catch (InputException $e) {
            $this->sezzleHelper->logSezzleActions("Report sync error - " . $e->getMessage());
        } catch (NoSuchEntityException $e) {
            $this->sezzleHelper->logSezzleActions("Report sync error - " . $e->getMessage());
        } catch (NotFoundException $e) {
            $this->sezzleHelper->logSezzleActions("Report sync error - " . $e->getMessage());
        } catch (LocalizedException $e) {
            $this->sezzleHelper->logSezzleActions("Report sync error - " . $e->getMessage());
        } catch (Exception $e) {
            $this->sezzleHelper->logSezzleActions("Report sync error - " . $e->getMessage());
        }
    }
}
