<?php
namespace Sezzle\Sezzlepay\Block;

use Magento\Framework\View\Element\Template;
use Sezzle\Sezzlepay\Model\Config\Container\ProductWidgetConfigInterface;
use Sezzle\Sezzlepay\Model\Config\Container\SezzleApiConfigInterface;

/**
 * Class ProductWidget
 * @package Sezzle\Sezzlepay\Block
 */
class ProductWidget extends Template
{

    const MIN_PRICE = 0;
    const MAX_PRICE = 100000;
    const WIDGET_TYPE = "product_page";

    /**
     * @var ProductWidgetConfigInterface
     */
    private $productWidgetConfig;

    /**
     * ProductWidget constructor.
     *
     * @param Template\Context $context
     * @param ProductWidgetConfigInterface $productWidgetConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ProductWidgetConfigInterface $productWidgetConfig,
        SezzleApiConfigInterface $sezzleApiConfig,
        array $data
    ) {
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
        return [
            'targetXPath'          => $this->productWidgetConfig->getTargetXPath(),
            'renderToPath'         => $this->productWidgetConfig->getRenderToPath(),
            'forcedShow'           => $this->productWidgetConfig->getForcedShow(),
            'alignment'            => $this->productWidgetConfig->getAlignment(),
            'merchantID'           => $this->sezzleApiConfig->getMerchantId(),
            'theme'                => $this->productWidgetConfig->getTheme(),
            'widthType'            => $this->productWidgetConfig->getWidthType(),
            'widgetType'           => self::WIDGET_TYPE,
            'minPrice'             => self::MIN_PRICE,
            'maxPrice'             => self::MAX_PRICE,
            'imageUrl'             => $this->productWidgetConfig->getImageUrl(),
            'hideClasses'          => $this->productWidgetConfig->getHideClass(),
        ];
    }
}
