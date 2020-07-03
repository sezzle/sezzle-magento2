<?php

namespace Sezzle\Sezzlepay\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotSaveException;
use Sezzle\Sezzlepay\Api\Data;
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

    public function __construct(
        SettlementReportsFactory $settlementReportsFactory,
        DataObjectHelper $dataObjectHelper,
        SettlementReportsRepositoryInterface $settlementReportsRepository,
        V2Interface $v2
    ) {
        $this->settlementReportsFactory = $settlementReportsFactory;
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
}
