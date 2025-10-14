<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Cart;

use Magento\Checkout\Model\CompositeConfigProvider;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Sezzle\Sezzlepay\Gateway\Config\Config;

/**
 * Class ExpressCheckout
 * @package Sezzle\Sezzlepay\Block\Cart
 */
class ExpressCheckout extends Template
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
     * @var CompositeConfigProvider
     */
    private $configProvider;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * ExpressCheckout constructor.
     * @param Context $context
     * @param Config $config
     * @param CheckoutSession $checkoutSession
     * @param CompositeConfigProvider $configProvider
     * @param SerializerInterface $serializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        CheckoutSession $checkoutSession,
        CompositeConfigProvider $configProvider,
        SerializerInterface $serializer,
        array $data = []
    ) {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->configProvider = $configProvider;
        $this->serializer = $serializer;
        parent::__construct($context, $data);
    }

    /**
     * Check if Sezzle payment button should be displayed
     *
     * @return bool
     */
    public function canDisplay(): bool
    {
        try {
            return $this->config->isEnabled()
                && $this->hasItemsInCart();
        } catch (NoSuchEntityException|InputException $e) {
            return false;
        }
    }

    /**
     * Check if cart has items
     *
     * @return bool
     */
    private function hasItemsInCart(): bool
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            return $quote && $quote->getItemsCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get checkout URL
     *
     * @return string
     */
    public function getCheckoutUrl(): string
    {
        return $this->getUrl('checkout', ['_secure' => true]);
    }

    /**
     * Retrieve checkout configuration
     *
     * @return array
     */
    public function getCheckoutConfig(): array
    {
        return $this->configProvider->getConfig();
    }

    /**
     * Retrieve serialized checkout config
     *
     * @return string
     */
    public function getSerializedCheckoutConfig(): string
    {
        return $this->serializer->serialize($this->getCheckoutConfig());
    }
}
