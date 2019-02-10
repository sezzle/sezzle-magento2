<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 * @license     https://www.sezzle.com/LICENSE.txt
 */

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
