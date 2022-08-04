<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Widget;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\ProductTypes\ConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Url\EncoderInterface;
use Sezzle\Sezzlepay\Helper\Data as SezzleHelper;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleConfigInterface;

class PDP extends View
{

    /**
     * @var SezzleConfigInterface
     */
    protected $sezzleConfig;
    /**
     * @var Data
     */
    protected $pricingHelper;
    /**
     * @var SezzleHelper
     */
    private $sezzleHelper;

    /**
     * AbstractWidget constructor.
     * @param Context $context
     * @param EncoderInterface $urlEncoder
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param StringUtils $string
     * @param Product $productHelper
     * @param ConfigInterface $productTypeConfig
     * @param FormatInterface $localeFormat
     * @param Session $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param SezzleConfigInterface $sezzleConfig
     * @param Data $pricingHelper
     * @param SezzleHelper $sezzleHelper
     * @param array $data
     */
    public function __construct(
        Context                                  $context,
        EncoderInterface                         $urlEncoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        StringUtils                              $string,
        Product                                  $productHelper,
        ConfigInterface                          $productTypeConfig,
        FormatInterface                          $localeFormat,
        Session                                  $customerSession,
        ProductRepositoryInterface               $productRepository,
        PriceCurrencyInterface                   $priceCurrency,
        SezzleConfigInterface                    $sezzleConfig,
        Data                                     $pricingHelper,
        SezzleHelper                             $sezzleHelper,
        array                                    $data = []
    )
    {
        $this->sezzleConfig = $sezzleConfig;
        $this->pricingHelper = $pricingHelper;
        $this->sezzleHelper = $sezzleHelper;
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency,
            $data
        );
    }

    /**
     * Get Merchant UUID
     *
     * @return string|null
     */
    public function getMerchantUUID()
    {
        return $this->sezzleConfig->getMerchantUUID();
    }

    /**
     * Get Widget Script for PDP status
     *
     * @return string
     */
    public function isWidgetEnabledForPDP()
    {
        try {
            return $this->sezzleConfig->isWidgetEnabledForPDP()
                && $this->sezzleConfig->isEnabled()
                && $this->getItemPrice() != '';
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getAlignment()
    {
        return "left";
    }

    /**
     * Get Item Price
     *
     * @return float|string
     */
    public function getItemPrice()
    {
        return $this->pricingHelper->currency(
            $this->getProduct()->getFinalPrice(),
            true,
            false
        );
    }

    /**
     * Get Widget URL
     *
     * @return mixed
     */
    public function getWidgetUrl()
    {
        if (!$this->getMerchantUUID()) {
            $this->sezzleHelper->logSezzleActions("Cannot provide widget URL as Merchant UUID is empty");
            return null;
        }
        $baseUrl = $this->sezzleConfig->getWidgetUrl('v1');
        $this->sezzleHelper->logSezzleActions("Widget URL served");
        return sprintf("$baseUrl/javascript/price-widget?uuid=%s", $this->getMerchantUUID());
    }
}
