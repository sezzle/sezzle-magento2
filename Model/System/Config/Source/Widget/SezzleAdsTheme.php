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
 * Colour schemes the Sezzle Ads placement ships in
 */
class SezzleAdsTheme implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => Config::SEZZLE_ADS_THEME_DARK,
                'label' => __('Dark'),
            ],
            [
                'value' => Config::SEZZLE_ADS_THEME_LIGHT,
                'label' => __('Light'),
            ]
        ];
    }
}
