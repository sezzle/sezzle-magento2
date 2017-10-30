<?php

namespace Sezzle\Sezzlepay\Model\Source\Product;

class ThemeAlignment implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'light',
                'label' => 'Light',
            ),
            array(
                'value' => 'dark',
                'label' => 'Dark',
            ),
        );
    }
}