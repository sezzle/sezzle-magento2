<?php

namespace Sezzle\Payment\Block\Widget;

use Magento\Catalog\Model\ResourceModel\Url;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;
use Sezzle\Payment\Model\System\Config\Container\SezzleApiConfigInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Checkout\Model\Session as CheckoutSession;

class Cart extends \Magento\Checkout\Block\Cart
{

    /**
     * @var SezzleApiConfigInterface
     */
    private $sezzleApiConfig;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        Url $catalogUrlBuilder,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Magento\Framework\App\Http\Context $httpContext,
        SezzleApiConfigInterface $sezzleApiConfig,
        array $data = []
    ) {
        $this->sezzleApiConfig = $sezzleApiConfig;
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
    public function isWidgetScriptAllowedForCartPage()
    {
        try {
            return $this->sezzleApiConfig->isWidgetScriptAllowedForCartPage()
                && $this->sezzleApiConfig->isEnabled()
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
        try {
            $totals = $this->getTotals();
            $firstTotal = reset($totals);
            if ($firstTotal) {
                $total = $firstTotal->getAddress()->getBaseGrandTotal();
                return $this->_storeManager->getStore()->getBaseCurrency()->format($total, [], true);
            }
            return '';
        } catch (NoSuchEntityException $e) {
            return '';
        }
    }

    /**
     * @return string
     */
    public function getAlignment()
    {
        return "right";
    }
}
