<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Api;

use Exception;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;

/**
 * Interface SettlementReportsManagementInterface
 * @package Sezzle\Sezzlepay\Api
 */
interface SettlementReportsManagementInterface
{

    /**
     * Sync and Save reports
     *
     * @throws LocalizedException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws NotFoundException
     * @throws Exception
     */
    public function syncAndSave();

    /**
     * Download settlement report details
     *
     * @param string $payoutUUID
     * @return ResponseInterface
     * @throws LocalizedException
     * @throws Exception
     * @throws NoSuchEntityException
     * @throws FileSystemException
     */
    public function downloadSettlementReportDetails($payoutUUID);

    /**
     * Get Payout Details in array format
     *
     * @param string $payoutUUID
     * @return array|bool|string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getPayoutDetails($payoutUUID);

}
