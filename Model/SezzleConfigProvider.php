<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Sezzle\Sezzlepay\Helper\Data;
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
    /**
     * @var Data
     */
    private $sezzleHelper;

    /**
     * SezzleConfigProvider constructor.
     * @param SezzleConfigInterface $sezzleConfig
     * @param Data $sezzleHelper
     */
    public function __construct(
        SezzleConfigInterface $sezzleConfig,
        Data $sezzleHelper
    ) {
        $this->sezzleHelper = $sezzleHelper;
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
                    'inContextMode' => $this->sezzleConfig->getInContextMode(),
                    'isMobileOrTablet' => $this->sezzleHelper->isMobileOrTablet()
                ]
            ]
        ];
    }
}
