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
use Magento\Framework\Stdlib\ArrayManager;
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
     * Where the Sezzle renderer keeps the flag inside the checkout layout
     */
    private const BILLING_ADDRESS_REQUIRED_PATH = 'components/checkout/children/steps/children/'
        . 'billing-step/children/payment/children/renders/children/sezzlepay/methods/sezzlepay/'
        . 'isBillingAddressRequired';

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
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @param Config $config
     * @param CheckoutSession $checkoutSession
     * @param Data $helper
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        Config          $config,
        CheckoutSession $checkoutSession,
        Data            $helper,
        ArrayManager    $arrayManager
    )
    {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
        $this->arrayManager = $arrayManager;
    }

    /**
     * @param LayoutProcessor $subject
     * @param array $jsLayout
     * @return array[]
     */
    public function beforeProcess(LayoutProcessor $subject, array $jsLayout): array
    {
        // Read the path before writing it. ArrayManager::set() populates, creating every
        // missing level on its way down, which would leave stray nodes in layouts that
        // carry no Sezzle renderer at all. get() returning null covers both a missing
        // path and a null flag, the same two cases the isset() this replaced ruled out.
        if ($this->arrayManager->get(self::BILLING_ADDRESS_REQUIRED_PATH, $jsLayout) === null) {
            return [$jsLayout];
        }

        if (!$this->isBillingAddressOptional()) {
            return [$jsLayout];
        }

        return [$this->arrayManager->set(self::BILLING_ADDRESS_REQUIRED_PATH, $jsLayout, false)];
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
