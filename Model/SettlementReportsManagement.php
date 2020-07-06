<?php

namespace Sezzle\Sezzlepay\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Sezzle\Sezzlepay\Api\Data\SettlementReportsInterface;
use Sezzle\Sezzlepay\Api\SettlementReportsRepositoryInterface;
use Sezzle\Sezzlepay\Api\V2Interface;

/**
 * Class SettlementReportsManagement
 * @package Sezzle\Sezzlepay\Model
 */
class SettlementReportsManagement implements \Sezzle\Sezzlepay\Api\SettlementReportsManagementInterface
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
     * SettlementReportsManagement constructor.
     * @param SettlementReportsFactory $settlementReportsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param Filesystem $filesystem
     * @param FileFactory $fileFactory
     * @param SettlementReportsRepositoryInterface $settlementReportsRepository
     * @param V2Interface $v2
     */
    public function __construct(
        SettlementReportsFactory $settlementReportsFactory,
        DataObjectHelper $dataObjectHelper,
        Filesystem $filesystem,
        FileFactory $fileFactory,
        SettlementReportsRepositoryInterface $settlementReportsRepository,
        V2Interface $v2
    ) {
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
    public function syncAndSave()
    {
        $settlementReports = $this->v2->getSettlementSummaries();
        $settlementReports = [
            0 => [
                'uuid' => 'b7916fbe-f30a-4435-b411-124634287a8ca',
                'payout_currency' => 'USD',
                'payout_date' => '2019-12-09T15:52:33Z',
                'net_settlement_amount' => 9370,
                'forex_fees' => 0,
                'status' => 'Complete',
            ],
            1 => [
                'uuid' => 'c51343hba-d54b-5641-e341-15235523b3at',
                'payout_currency' => 'USD',
                'payout_date' => '2019-12-10T15:52:33Z',
                'net_settlement_amount' => 23470,
                'forex_fees' => 0,
                'status' => 'Complete',
            ],
        ];
        $reportsArray = [];
        if (!empty($settlementReports)) {
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
    }

    /**
     * @inheritDoc
     */
    public function downloadSettlementReportDetails($payoutUUID)
    {
        $details = $this->v2->getSettlementDetails($payoutUUID);

        $details = "total_order_amount,total_refund_amount,total_fee_amount,total_returned_fee_amount,total_chargeback_amount,total_chargeback_reversal_amount,total_correction_amount,total_referral_revenue_transfer_amount,total_bank_account_withdrawals,total_bank_account_withdrawal_reversals,forex_fees,net_settlement_amount,payment_uuid,settlement_currency,payout_date,payout_status
10.00,0.00,-0.60,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.18,9.22,51ce75eb-7156-48a5-9cb2-c31774a76570,,2018-02-08 01:07:27 +0000 UTC,Pending
type,order_capture_date,order_created_at,event_date,order_uuid,customer_order_id,external_reference_id,amount,posting_currency,type_code,chargeback_code
ORDER,2018-01-30T18:24:12Z,2018-01-30T18:24:12Z,2018-01-30T18:24:12Z,b9obg-irk6g-0000a-8is70,3,100000074,10.00,USD,001,
FEE,2018-01-30T18:24:12Z,2018-01-30T18:24:12Z,0001-01-01T00:00:00Z,b9obg-irk6g-0000a-8is70,3,100000074,-0.60,USD,003,";

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
     * @param string $payoutUUID
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getPayoutDetails($payoutUUID)
    {
        //return $this->v2->getSettlementDetails($payoutUUID);

        return "total_order_amount,total_refund_amount,total_fee_amount,total_returned_fee_amount,total_chargeback_amount,total_chargeback_reversal_amount,total_correction_amount,total_referral_revenue_transfer_amount,total_bank_account_withdrawals,total_bank_account_withdrawal_reversals,forex_fees,net_settlement_amount,payment_uuid,settlement_currency,payout_date,payout_status
10.00,0.00,-0.60,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.18,9.22,51ce75eb-7156-48a5-9cb2-c31774a76570,,2018-02-08 01:07:27 +0000 UTC,Pending
type,order_capture_date,order_created_at,event_date,order_uuid,customer_order_id,external_reference_id,amount,posting_currency,type_code,chargeback_code
ORDER,2018-01-30T18:24:12Z,2018-01-30T18:24:12Z,2018-01-30T18:24:12Z,b9obg-irk6g-0000a-8is70,3,100000074,10.00,USD,001,
FEE,2018-01-30T18:24:12Z,2018-01-30T18:24:12Z,0001-01-01T00:00:00Z,b9obg-irk6g-0000a-8is70,3,100000074,-0.60,USD,003,";
    }
}
