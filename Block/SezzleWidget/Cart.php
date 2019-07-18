<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\SezzleWidget;

use Magento\Framework\View\Element\Template;
use Sezzle\Sezzlepay\Model\Config\Container\CartWidgetConfigInterface;
use Sezzle\Sezzlepay\Model\Config\Container\SezzleApiConfigInterface;

/**
 * Class Cart
 * @package Sezzle\Sezzlepay\Block\SezzleWidget
 */
class Cart extends Template
{

    const MIN_PRICE = 0;
    const MAX_PRICE = 100000;
    const WIDGET_TYPE = "cart";

    /**
     * @var CartWidgetConfigInterface
     */
    private $cartWidgetConfig;
    /**
     * @var SezzleApiConfigInterface
     */
    private $sezzleApiConfig;

    /**
     * ProductWidget constructor.
     *
     * @param Template\Context $context
     * @param CartWidgetConfigInterface $cartWidgetConfig
     * @param SezzleApiConfigInterface $sezzleApiConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CartWidgetConfigInterface $cartWidgetConfig,
        SezzleApiConfigInterface $sezzleApiConfig,
        array $data
    )
    {
        $this->cartWidgetConfig = $cartWidgetConfig;
        $this->sezzleApiConfig = $sezzleApiConfig;
        parent::__construct($context, $data);
    }

    /**
     * Get JS Config
     *
     * @return array
     */
    public function getJsConfig()
    {
        $result = [
            'targetXPath' => $this->cartWidgetConfig->getTargetXPath(),
            'renderToPath' => $this->cartWidgetConfig->getRenderToPath(),
            'forcedShow' => $this->cartWidgetConfig->getForcedShow(),
            'alignment' => $this->cartWidgetConfig->getAlignment(),
            'merchantID' => $this->sezzleApiConfig->getMerchantId(),
            'theme' => $this->cartWidgetConfig->getTheme(),
            'widthType' => $this->cartWidgetConfig->getWidthType(),
            'widgetType' => self::WIDGET_TYPE,
            'minPrice' => self::MIN_PRICE,
            'maxPrice' => self::MAX_PRICE,
            'imageUrl' => $this->cartWidgetConfig->getImageUrl(),
            'hideClasses' => $this->cartWidgetConfig->getHideClass()
        ];

        foreach ($result as $key => $value) {
            if (is_null($result[$key]) || $result[$key] == '') {
                unset($result[$key]);
            } 
        }
        return $result;
    }
}
