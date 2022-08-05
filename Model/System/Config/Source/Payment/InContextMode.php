<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\System\Config\Source\Payment;

use Magento\Framework\Data\OptionSourceInterface;
use Sezzle\Sezzlepay\Gateway\Config\Config;

/**
 * Class InContextMode
 * @package Sezzle\Sezzlepay\Model\System\Config\Source\Payment
 */
class InContextMode implements OptionSourceInterface
{

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => Config::INCONTEXT_MODE_IFRAME,
                'label' => 'IFrame',
            ],
            [
                'value' => Config::INCONTEXT_MODE_POPUP,
                'label' => 'Pop Up',
            ]
        ];
    }
}
