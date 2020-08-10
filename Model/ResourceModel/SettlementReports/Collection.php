<?php
/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Model\ResourceModel\SettlementReports;

/**
 * Class Collection
 * @package Sezzle\Sezzlepay\Model\ResourceModel\SettlementReports
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\VersionControl\Collection
{

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Sezzle\Sezzlepay\Model\SettlementReports::class, \Sezzle\Sezzlepay\Model\ResourceModel\SettlementReports::class);
    }
}
