<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Config\Source\Payment;

/**
 * Class Mode
 * @package Sezzle\Sezzlepay\Model\Config\Source\Payment
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
