<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\System\Config\Source\Widget;

use Magento\Framework\Data\OptionSourceInterface;
use Sezzle\Sezzlepay\Gateway\Config\Config;

/**
 * Positions on the checkout success page where the Sezzle Ads placement can render
 */
class SezzleAdsPlacement implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => Config::SEZZLE_ADS_PLACEMENT_MAIN_BOTTOM,
                'label' => __('Bottom of page content'),
            ],
            [
                'value' => Config::SEZZLE_ADS_PLACEMENT_AFTER_CONFIRMATION,
                'label' => __('Below order confirmation message'),
            ],
            [
                'value' => Config::SEZZLE_ADS_PLACEMENT_MAIN_TOP,
                'label' => __('Top of page content'),
            ]
        ];
    }
}
