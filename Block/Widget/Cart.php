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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\View\Element\Template\Context;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleConfigInterface;
use Sezzle\Sezzlepay\Helper\Data as SezzleHelper;

/**
 * Class Cart
 * @package Sezzle\Sezzlepay\Block\Widget
 */
class Cart extends \Magento\Checkout\Block\Cart
{

    /**
     * @var SezzleConfigInterface
     */
    private $sezzleConfig;
    /**
     * @var Data
     */
    private $pricingHelper;
    /**
     * @var SezzleHelper
     */
    private $sezzleHelper;

    /**
     * Cart constructor.
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param Url $catalogUrlBuilder
     * @param \Magento\Checkout\Helper\Cart $cartHelper
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param SezzleConfigInterface $sezzleConfig
     * @param Data $pricingHelper
     * @param SezzleHelper $sezzleHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        Url $catalogUrlBuilder,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Magento\Framework\App\Http\Context $httpContext,
        SezzleConfigInterface $sezzleConfig,
        Data $pricingHelper,
        SezzleHelper $sezzleHelper,
        array $data = []
    ) {
        $this->sezzleConfig = $sezzleConfig;
        $this->pricingHelper = $pricingHelper;
        $this->sezzleHelper = $sezzleHelper;
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
     * Get Merchant UUID
     *
     * @return string|null
     */
    public function getMerchantUUID()
    {
        try {
            return $this->sezzleConfig->getMerchantUUID();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Get Widget Type
     *
     * @return string
     */
    public function getWidgetType()
    {
        return "standard";
    }

    /**
     * Get Widget Script for Cart Page status
     *
     * @return string
     */
    public function isWidgetEnabledForCartPage()
    {
        try {
            return $this->sezzleConfig->isWidgetEnabledForCartPage()
                && $this->sezzleConfig->isEnabled()
                && $this->getGrandTotal() != '';
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getGrandTotal()
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
    public function getAlignment()
    {
        return "right";
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
        $gatewayRegion = $this->sezzleConfig->getGatewayRegion();
        $baseUrl = $this->sezzleConfig->getWidgetUrl('v1', $gatewayRegion);
        $this->sezzleHelper->logSezzleActions("Widget URL served");
        return sprintf("$baseUrl/javascript/price-widget?uuid=%s", $this->getMerchantUUID());
    }
}
