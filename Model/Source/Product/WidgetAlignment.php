<?php

namespace Sezzle\Sezzlepay\Model\Source\Product;

class WidgetAlignment implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'center',
                'label' => 'Center',
            ),
            array(
                'value' => 'right',
                'label' => 'Right',
            ),
            array(
                'value' => 'left',
                'label' => 'Left',
            ),
        );
    }
}