<?php


namespace Sezzle\Sezzlepay\Api;


use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface SettlementReportsManagementInterface
 * @package Sezzle\Sezzlepay\Api
 */
interface SettlementReportsManagementInterface
{

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function syncAndSave();

    /**
     * @param string $payoutUUID
     * @return ResponseInterface
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function downloadSettlementReportDetails($payoutUUID);
    public function getPayoutDetails($payoutUUID);

}
