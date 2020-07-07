<?php
/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Model;

use Sezzle\Sezzlepay\Api\SettlementReportsRepositoryInterface;

/**
 * Class SettlementReportsRepository
 * @package Sezzle\Sezzlepay\Model
 */
class SettlementReportsRepository implements SettlementReportsRepositoryInterface
{
    /**
     * @var ResourceModel\SettlementReports\CollectionFactory
     */
    private $settlementReportsCollectionFactory;

    /**
     * SettlementReportsRepository constructor.
     * @param ResourceModel\SettlementReports\CollectionFactory $settlementReportsCollectionFactory
     */
    public function __construct(
        ResourceModel\SettlementReports\CollectionFactory $settlementReportsCollectionFactory
    ) {
        $this->settlementReportsCollectionFactory = $settlementReportsCollectionFactory;
    }


    /**
     * @inheritDoc
     */
    public function saveMultiple(array $settlementReports = null)
    {
        $collection = $this->settlementReportsCollectionFactory->create();
        $syncedPayoutUUIDs = $collection->getColumnValues('uuid');

        foreach ($settlementReports as $settlementReport) {
            if (!in_array($settlementReport->getUuid(), $syncedPayoutUUIDs)) {
                $collection->addItem($settlementReport)->save();
            }
        }
    }
}
