<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Block\Widget;

use Magento\Catalog\Model\ResourceModel\Url;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\View\Element\Template\Context;
use Sezzle\Payment\Model\System\Config\Container\SezzleConfigInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class Cart
 * @package Sezzle\Payment\Block\Widget
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
     * Cart constructor.
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param Url $catalogUrlBuilder
     * @param \Magento\Checkout\Helper\Cart $cartHelper
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param SezzleConfigInterface $sezzleConfig
     * @param Data $pricingHelper
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
        array $data = []
    ) {
        $this->sezzleConfig = $sezzleConfig;
        $this->pricingHelper = $pricingHelper;
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
}
