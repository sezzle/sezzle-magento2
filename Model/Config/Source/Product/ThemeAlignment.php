<?php

namespace Sezzle\Sezzlepay\Model\Config\Source\Product;

/**
 * Class ThemeAlignment
 * @package Sezzle\Sezzlepay\Model\Config\Source\Product
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
