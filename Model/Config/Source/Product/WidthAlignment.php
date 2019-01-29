<?php

namespace Sezzle\Sezzlepay\Model\Config\Source\Product;

/**
 * Class WidthAlignment
 * @package Sezzle\Sezzlepay\Model\Config\Source\Product
 */
class WidthAlignment implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @return array
     */
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
