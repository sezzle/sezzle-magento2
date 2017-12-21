<?php

namespace Sezzle\Sezzlepay\Model\Source\Product;

class WidgetAlignment implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [
            [
                'value' => 'center',
                'label' => 'Center',
            ],
            [
                'value' => 'right',
                'label' => 'Right',
            ],
            [
                'value' => 'left',
                'label' => 'Left',
            ],
        ];
    }
}
