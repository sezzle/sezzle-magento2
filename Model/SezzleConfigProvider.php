<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleConfigInterface;

/**
 * Class SezzleConfigProvider
 * @package Sezzle\Sezzlepay\Model
 */
class SezzleConfigProvider implements ConfigProviderInterface
{

    /**
     * @var SezzleConfigInterface
     */
    private $sezzleConfig;

    public function __construct(SezzleConfigInterface $sezzleConfig)
    {
        $this->sezzleConfig = $sezzleConfig;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                Sezzle::PAYMENT_CODE => [
                    'methodCode' => Sezzle::PAYMENT_CODE,
                    'isInContextCheckout' => (bool)$this->sezzleConfig->isInContextModeEnabled(),
                    'inContextMode' => $this->sezzleConfig->getInContextMode()
                ]
            ]
        ];
    }
}
