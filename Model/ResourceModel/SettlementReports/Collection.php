<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sezzle\Sezzlepay\Model\ResourceModel\SettlementReports;

/**
 * Customers collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
