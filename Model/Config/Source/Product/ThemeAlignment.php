<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model\Config\Source\Product;

/**
 * Class ThemeAlignment
 * @package Sezzle\Payment\Model\Config\Source\Product
 */
class ThemeAlignment implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'light',
                'label' => 'Light',
            ],
            [
                'value' => 'dark',
                'label' => 'Dark',
            ],
        ];
    }
}
