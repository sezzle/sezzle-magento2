<?php

namespace Sezzle\Sezzlepay\Model\Source;

class ApiMode implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'sandbox',
                'label' => 'Sandbox',
            ),
            array(
                'value' => 'live',
                'label' => 'Live',
            ),
        );

    }
}