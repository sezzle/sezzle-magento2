<?php

namespace Sezzle\Sezzlepay\Model\Source\Product;

class WidthAlignment implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [
            [
                'value' => 'thin',
                'label' => 'Thin',
            ],
            [
                'value' => 'thick',
                'label' => 'Thick',
            ],
        ];
    }
}
