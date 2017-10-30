<?php

namespace Sezzle\Sezzlepay\Model\Source\Product;

class WidthAlignment implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'thin',
                'label' => 'Thin',
            ),
            array(
                'value' => 'thick',
                'label' => 'Thick',
            ),
        );
    }
}