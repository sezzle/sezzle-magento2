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
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\StringUtils;
use Magento\Framework\Url\EncoderInterface;
use Sezzle\Sezzlepay\Helper\Data as helper;
use Sezzle\Sezzlepay\Gateway\Config\Config;

class PDP extends View
{

    /**
     * @var Config
     */
    protected $config;
    /**
     * @var Data
     */
    protected $pricingHelper;
    /**
     * @var helper
     */
    private $helper;

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
     * @param Config $config
     * @param Data $pricingHelper
     * @param helper $helper
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
        Config                                   $config,
        Data                                     $pricingHelper,
        helper                                   $helper,
        array                                    $data = []
    )
    {
        $this->config = $config;
        $this->pricingHelper = $pricingHelper;
        $this->helper = $helper;
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
     * Get Widget Script for PDP status
     *
     * @return bool
     */
    public function isWidgetEnabledForPDP(): bool
    {
        try {
            return $this->config->isWidgetEnabledForPDP()
                && $this->config->isEnabled()
                && $this->getItemPrice() != '';
        } catch (NoSuchEntityException|InputException $e) {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getAlignment(): string
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
     * @return string
     */
    public function getWidgetURL(): string
    {
        try {
            if (!$merchantUUID = $this->config->getMerchantUUID()) {
                $this->helper->logSezzleActions("Cannot provide widget URL as Merchant UUID is empty");
                return '';
            }
        } catch (InputException|NoSuchEntityException $e) {
            return '';
        }

        $baseUrl = $this->config->getWidgetURL(Config::API_VERSION_V1);
        $this->helper->logSezzleActions("Widget URL served");
        return sprintf("$baseUrl/javascript/price-widget?uuid=%s", $merchantUUID);
    }
}
