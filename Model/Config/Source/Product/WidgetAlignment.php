<?php

namespace Sezzle\Sezzlepay\Model\Config\Source\Product;

/**
 * Class WidgetAlignment
 * @package Sezzle\Sezzlepay\Model\Config\Source\Product
 */
class WidgetAlignment implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @return array
     */
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
