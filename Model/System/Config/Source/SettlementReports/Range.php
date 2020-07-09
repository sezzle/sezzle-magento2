<?php
/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */
namespace Sezzle\Sezzlepay\Model\System\Config\Source\SettlementReports;

/**
 * Source model for available settlement report fetching intervals
 */
class Range implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            7 => __("Last 7 days"),
            14 => __("Last 14 days"),
            21 => __("Last 21 days"),
            28 => __("Last 28 days"),
            35 => __("Last 35 days")
        ];
    }
}
