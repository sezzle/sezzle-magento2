<?php

namespace Sezzle\Sezzlepay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class SezzleConfigProvider
 * @package Sezzle\Sezzlepay\Model
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
                'sezzlepay' => [
                    'methodCode' => "sezzlepay"
                ]
            ]
        ];
    }
}
