<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class SezzleConfigProvider
 * @package Sezzle\Payment\Model
 */
class SezzleConfigProvider implements ConfigProviderInterface
{

    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                'sezzle' => [
                    'methodCode' => "sezzle"
                ]
            ]
        ];
    }
}
