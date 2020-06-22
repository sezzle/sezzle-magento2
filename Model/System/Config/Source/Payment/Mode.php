<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model\System\Config\Source\Payment;

use Magento\Framework\Option\ArrayInterface;

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
                'value' => 'live',
                'label' => 'Live',
            ],
            [
                'value' => 'sandbox',
                'label' => 'Sandbox',
            ]
        ];
    }
}
