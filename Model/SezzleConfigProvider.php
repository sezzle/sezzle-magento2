<?php
namespace Sezzle\Sezzlepay\Model;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\UrlInterface as UrlInterface;

class SezzleConfigProvider implements ConfigProviderInterface
{
    protected $methodCode = "sezzlepay";
    
    protected $urlBuilder;
    public function __construct(UrlInterface $urlBuilder) {
        $this->urlBuilder = $urlBuilder;
    }

    public function getConfig()
    {
        return [
            'payment' => [
                'sezzlepay' => [
                    'redirectUrl' => $this->urlBuilder->getUrl('sezzlepay/standard/redirect', ['_secure' => true])
                ]
            ]
        ];
    }
}