<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Widget;

use Magento\Catalog\Model\ResourceModel\Url;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\View\Element\Template\Context;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Helper\Data as helper;

/**
 * Class Cart
 * @package Sezzle\Sezzlepay\Block\Widget
 */
class Cart extends \Magento\Checkout\Block\Cart
{

    /**
     * @var Config
     */
    private $config;
    /**
     * @var Data
     */
    private $pricingHelper;
    /**
     * @var helper
     */
    private $helper;

    /**
     * Cart constructor.
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param Url $catalogUrlBuilder
     * @param \Magento\Checkout\Helper\Cart $cartHelper
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param Config $config
     * @param Data $pricingHelper
     * @param helper $helper
     * @param array $data
     */
    public function __construct(
        Context                             $context,
        CustomerSession                     $customerSession,
        CheckoutSession                     $checkoutSession,
        Url                                 $catalogUrlBuilder,
        \Magento\Checkout\Helper\Cart       $cartHelper,
        \Magento\Framework\App\Http\Context $httpContext,
        Config                              $config,
        Data                                $pricingHelper,
        helper                              $helper,
        array                               $data = []
    )
    {
        $this->config = $config;
        $this->pricingHelper = $pricingHelper;
        $this->helper = $helper;
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $catalogUrlBuilder,
            $cartHelper,
            $httpContext,
            $data
        );
    }

    /**
     * Get Widget Script for Cart Page status
     *
     * @return bool
     */
    public function isWidgetEnabledForCartPage(): bool
    {
        try {
            return $this->config->isWidgetEnabledForCart()
                && $this->config->isEnabled()
                && $this->getGrandTotal() != '';
        } catch (NoSuchEntityException|InputException $e) {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getGrandTotal(): string
    {
        $totals = $this->getTotals();
        $firstTotal = reset($totals);
        if ($firstTotal) {
            return $this->pricingHelper->currency(
                $firstTotal->getAddress()->getBaseGrandTotal(),
                true,
                false
            );
        }
        return '';
    }

    /**
     * @return string
     */
    public function getAlignment(): string
    {
        return "right";
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
