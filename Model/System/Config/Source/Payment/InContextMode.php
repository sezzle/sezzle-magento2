<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\System\Config\Source\Payment;

use Magento\Framework\Option\ArrayInterface;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleIdentity;

/**
 * Class InContextMode
 * @package Sezzle\Sezzlepay\Model\System\Config\Source\Payment
 */
class InContextMode implements ArrayInterface
{

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => SezzleIdentity::INCONTEXT_MODE_IFRAME,
                'label' => 'IFrame',
            ],
            [
                'value' => SezzleIdentity::INCONTEXT_MODE_POPUP,
                'label' => 'Pop Up',
            ]
        ];
    }
}
