<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

declare(strict_types=1);

namespace Sezzle\Sezzlepay\Model\Config\Source\Payment;

use Magento\Framework\Option\ArrayInterface;

/**
 * Sezzle Payment Action Dropdown source
 */
class PaymentAction implements ArrayInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => \Sezzle\Sezzlepay\Model\SezzlePay::ACTION_AUTHORIZE,
                'label' => __('Authorize Only'),
            ],
            [
                'value' => \Sezzle\Sezzlepay\Model\SezzlePay::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize and Capture')
            ]
        ];
    }
}
