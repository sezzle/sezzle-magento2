<?php


namespace Sezzle\Sezzlepay\Model\ResourceModel;


use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;

class SettlementReports extends AbstractDb
{

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('sezzle_settlement_reports', 'entity_id');
    }
}
