<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 * @license     https://www.sezzle.com/LICENSE.txt
 */

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
