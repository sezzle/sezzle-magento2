<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\SezzleWidget;

use Magento\Framework\View\Element\Template;
use Sezzle\Sezzlepay\Model\Config\Container\ProductWidgetConfigInterface;
use Sezzle\Sezzlepay\Model\Config\Container\SezzleApiConfigInterface;

/**
 * Class ProductView
 * @package Sezzle\Sezzlepay\Block\SezzleWidget
 */
class ProductView extends Template
{

    const MIN_PRICE = 0;
    const MAX_PRICE = 100000;
    const WIDGET_TYPE = "product_page";

    /**
     * @var ProductWidgetConfigInterface
     */
    private $productWidgetConfig;
    /**
     * @var SezzleApiConfigInterface
     */
    private $sezzleApiConfig;

    /**
     * ProductWidget constructor.
     *
     * @param Template\Context $context
     * @param ProductWidgetConfigInterface $productWidgetConfig
     * @param SezzleApiConfigInterface $sezzleApiConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ProductWidgetConfigInterface $productWidgetConfig,
        SezzleApiConfigInterface $sezzleApiConfig,
        array $data
    )
    {
        $this->productWidgetConfig = $productWidgetConfig;
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
            'targetXPath' => $this->productWidgetConfig->getTargetXPath(),
            'renderToPath' => $this->productWidgetConfig->getRenderToPath(),
            'forcedShow' => $this->productWidgetConfig->getForcedShow(),
            'alignment' => $this->productWidgetConfig->getAlignment(),
            'merchantID' => $this->sezzleApiConfig->getMerchantId(),
            'theme' => $this->productWidgetConfig->getTheme(),
            'widthType' => $this->productWidgetConfig->getWidthType(),
            'widgetType' => self::WIDGET_TYPE,
            'minPrice' => self::MIN_PRICE,
            'maxPrice' => self::MAX_PRICE,
            'imageUrl' => $this->productWidgetConfig->getImageUrl(),
            'hideClasses' => $this->productWidgetConfig->getHideClass()
        ];

        foreach ($result as $key => $value) {
            if (is_null($result[$key]) || $result[$key] == '') {
                unset($result[$key]);
            } 
        }
        return $result;
    }
}
