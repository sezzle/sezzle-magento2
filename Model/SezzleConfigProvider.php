<?php
namespace Sezzle\Sezzlepay\Model;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface as UrlInterface;

class SezzleConfigProvider implements ConfigProviderInterface
{

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