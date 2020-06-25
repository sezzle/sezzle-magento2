<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model\System\Config\Source\Payment;

use Magento\Framework\Option\ArrayInterface;
use Sezzle\Payment\Model\System\Config\Container\SezzleIdentity;

/**
 * Class Mode
 * @package Sezzle\Payment\Model\System\Config\Source\Payment
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
