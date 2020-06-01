<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model\Config\Source\Payment;

/**
 * Class Mode
 * @package Sezzle\Payment\Model\Config\Source\Payment
 */
class Mode implements \Magento\Framework\Option\ArrayInterface
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
