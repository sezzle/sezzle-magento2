<?php

namespace Sezzle\Sezzlepay\Model;

use Magento\Framework\Exception\AlreadyExistsException;
use Sezzle\Sezzlepay\Api\Data\SettlementReportsInterface;

class SettlementReportsRepository implements \Sezzle\Sezzlepay\Api\SettlementReportsRepositoryInterface
{
    /**
     * @var SettlementReportsFactory
     */
    private $settlementReportsFactory;
    /**
     * @var ResourceModel\SettlementReports
     */
    private $settlementReportsResourceModel;
    /**
     * @var ResourceModel\SettlementReports\CollectionFactory
     */
    private $settlementReportsCollectionFactory;

    public function __construct(
        SettlementReportsFactory $settlementReportsFactory,
        ResourceModel\SettlementReports $settlementReportsResourceModel,
        ResourceModel\SettlementReports\CollectionFactory $settlementReportsCollectionFactory
    ) {
        $this->settlementReportsFactory = $settlementReportsFactory;
        $this->settlementReportsResourceModel = $settlementReportsResourceModel;
        $this->settlementReportsCollectionFactory = $settlementReportsCollectionFactory;
    }

    /**
     * @param SettlementReportsInterface|SettlementReports $settlementReports
     */
    public function save(SettlementReportsInterface $settlementReports)
    {
        try {
            $this->settlementReportsResourceModel->save($settlementReports);
        } catch (AlreadyExistsException $e) {
        } catch (\Exception $e) {
        }
    }

    /**
     * @param SettlementReportsInterface[] $settlementReports
     * @throws \Exception
     */
    public function saveMultiple(array $settlementReports = null)
    {
        $collection = $this->settlementReportsCollectionFactory->create();
        foreach ($settlementReports as $settlementReport) {
            if (!$collection->addFieldToFilter('uuid', $settlementReport->getUuid())->getSize()) {
                $collection->addItem($settlementReport)->save();
            }
        }
    }
}
