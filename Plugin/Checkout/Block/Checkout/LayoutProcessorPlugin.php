<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Plugin\Checkout\Block\Checkout;

use Exception;
use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Checkout\Model\Session as CheckoutSession;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Helper\Data;

/**
 * Drops the billing address form from the Sezzle payment method when the merchant has
 * turned off "Require Billing Address".
 *
 * LayoutProcessor::processPaymentConfiguration() reads isBillingAddressRequired while
 * building the layout, so the flag has to be lowered before process() runs. Once it is
 * off, Magento renders no billing form and no "same as shipping" checkbox, and
 * checkout-data-resolver reuses the shipping address as the billing address.
 */
class LayoutProcessorPlugin
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Data
     */
    private $helper;

    /**
     * @param Config $config
     * @param CheckoutSession $checkoutSession
     * @param Data $helper
     */
    public function __construct(
        Config          $config,
        CheckoutSession $checkoutSession,
        Data            $helper
    )
    {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
    }

    /**
     * @param LayoutProcessor $subject
     * @param array $jsLayout
     * @return array[]
     */
    public function beforeProcess(LayoutProcessor $subject, array $jsLayout): array
    {
        // Read the path before writing it. Taking a reference to a missing key would
        // create every level of it, leaving stray nodes in layouts that carry no Sezzle
        // renderer at all.
        if (!isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
            ['children']['payment']['children']['renders']['children']['sezzlepay']['methods']['sezzlepay']
            ['isBillingAddressRequired'])) {
            return [$jsLayout];
        }

        if (!$this->isBillingAddressOptional()) {
            return [$jsLayout];
        }

        $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']
            ['children']['payment']['children']['renders']['children']['sezzlepay']['methods']['sezzlepay']
            ['isBillingAddressRequired'] = false;

        return [$jsLayout];
    }

    /**
     * Whether the billing address form can be dropped for this quote
     *
     * A virtual quote has no shipping address to reuse, so removing its billing form
     * would leave the shopper with no way to supply one at all.
     *
     * @return bool
     */
    private function isBillingAddressOptional(): bool
    {
        try {
            if ($this->config->isBillingAddressRequired()) {
                return false;
            }

            return !$this->checkoutSession->getQuote()->isVirtual();
        } catch (Exception $e) {
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Could not resolve billing address requirement; leaving it required.',
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
