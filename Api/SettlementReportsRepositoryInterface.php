<?php


namespace Sezzle\Sezzlepay\Api;


interface SettlementReportsRepositoryInterface
{
    public function save(\Sezzle\Sezzlepay\Api\Data\SettlementReportsInterface $settlementReports);

    /**
     * @param Data\SettlementReportsInterface[] $settlementReports
     * @return mixed
     */
    public function saveMultiple(array $settlementReports = null);

}
