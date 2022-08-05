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
 * Class Mode
 * @package Sezzle\Sezzlepay\Model\System\Config\Source\Payment
 */
class Mode implements OptionSourceInterface
{

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => Config::PAYMENT_MODE_LIVE,
                'label' => 'Live',
            ],
            [
                'value' => Config::PAYMENT_MODE_SANDBOX,
                'label' => 'Sandbox',
            ]
        ];
    }
}
