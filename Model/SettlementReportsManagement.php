<?php
/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem;
use Sezzle\Sezzlepay\Api\Data\SettlementReportsInterface;
use Sezzle\Sezzlepay\Api\SettlementReportsManagementInterface;
use Sezzle\Sezzlepay\Api\SettlementReportsRepositoryInterface;
use Sezzle\Sezzlepay\Api\V2Interface;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * Class SettlementReportsManagement
 * @package Sezzle\Sezzlepay\Model
 */
class SettlementReportsManagement implements SettlementReportsManagementInterface
{
    /**
     * @var SettlementReportsFactory
     */
    private $settlementReportsFactory;
    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;
    /**
     * @var SettlementReportsRepositoryInterface
     */
    private $settlementReportsRepository;
    /**
     * @var V2Interface
     */
    private $v2;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var FileFactory
     */
    private $fileFactory;
    /**
     * @var Data
     */
    private $sezzleHelper;

    /**
     * SettlementReportsManagement constructor.
     * @param SettlementReportsFactory $settlementReportsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param Filesystem $filesystem
     * @param Data $sezzleHelper
     * @param FileFactory $fileFactory
     * @param SettlementReportsRepositoryInterface $settlementReportsRepository
     * @param V2Interface $v2
     */
    public function __construct(
        SettlementReportsFactory $settlementReportsFactory,
        DataObjectHelper $dataObjectHelper,
        Filesystem $filesystem,
        Data $sezzleHelper,
        FileFactory $fileFactory,
        SettlementReportsRepositoryInterface $settlementReportsRepository,
        V2Interface $v2
    ) {
        $this->sezzleHelper = $sezzleHelper;
        $this->settlementReportsFactory = $settlementReportsFactory;
        $this->filesystem = $filesystem;
        $this->fileFactory = $fileFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->settlementReportsRepository = $settlementReportsRepository;
        $this->v2 = $v2;
    }

    /**
     * @inheritDoc
     */
    public function syncAndSave($from = null, $to = null)
    {
        $settlementReports = $this->v2->getSettlementSummaries($from, $to);
        if (empty($settlementReports)) {
            throw new NotFoundException(__("No report found."));
        } elseif (isset($settlementReports['id']) && $settlementReports['id'] == 'error') {
            throw new InputException(__($settlementReports['message']));
        }
        $reportsArray = [];
        foreach ($settlementReports as $settlementReport) {
            $settlementReportsModel = $this->settlementReportsFactory->create();
            $this->dataObjectHelper->populateWithArray(
                $settlementReportsModel,
                $settlementReport,
                SettlementReportsInterface::class
            );
            $reportsArray[] = $settlementReportsModel;
        }
        $this->settlementReportsRepository->saveMultiple($reportsArray);
    }


    /**
     * @inheritDoc
     */
    public function downloadSettlementReportDetails($payoutUUID)
    {
        $details = $this->v2->getSettlementDetails($payoutUUID);

        if (is_array($details) && $details['status'] == '404') {
            throw new LocalizedException(
                __('Invalid Payout UUID is provided.')
            );
        }

        $dir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $fileName = sprintf('%s.csv', $payoutUUID);

        $response = $this->fileFactory->create(
            $fileName,
            $details
        );

        $dir->delete($fileName);
        return $response;
    }


    /**
     * @inheritDoc
     */
    public function getPayoutDetails($payoutUUID)
    {
        $csvData = $this->v2->getSettlementDetails($payoutUUID);
        if (!$csvData) {
            return false;
        }
        return $this->sezzleHelper->csvToArray($csvData);
    }
}
