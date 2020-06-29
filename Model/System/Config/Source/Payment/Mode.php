<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\System\Config\Source\Payment;

use Magento\Framework\Option\ArrayInterface;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleIdentity;

/**
 * Class Mode
 * @package Sezzle\Sezzlepay\Model\System\Config\Source\Payment
 */
class Mode implements ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => SezzleIdentity::PROD_MODE,
                'label' => 'Live',
            ],
            [
                'value' => SezzleIdentity::SANDBOX_MODE,
                'label' => 'Sandbox',
            ]
        ];
    }
}
