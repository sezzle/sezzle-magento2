<?php

namespace Sezzle\Sezzlepay\Model\Source\Product;

class ThemeAlignment implements \Magento\Framework\Option\ArrayInterface
{

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
