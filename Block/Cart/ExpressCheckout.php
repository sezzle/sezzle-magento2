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
use Sezzle\Sezzlepay\Gateway\Http\TransferFactory;
use Sezzle\Sezzlepay\Gateway\Http\Client;
use Sezzle\Sezzlepay\Helper\Data as Helper;

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
     * @var Helper
     */
    private $helper;

    /**
     * @var TransferFactory
     */
    private $transferFactory;

    /**
     * @var Client
     */
    private $client;

    /**
     * ExpressCheckout constructor.
     * @param Context $context
     * @param Config $config
     * @param CheckoutSession $checkoutSession
     * @param CompositeConfigProvider $configProvider
     * @param SerializerInterface $serializer
     * @param Helper $helper
     * @param TransferFactory $transferFactory
     * @param Client $client
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        CheckoutSession $checkoutSession,
        CompositeConfigProvider $configProvider,
        SerializerInterface $serializer,
        Helper $helper,
        TransferFactory $transferFactory,
        Client $client,
        array $data = []
    ) {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->configProvider = $configProvider;
        $this->serializer = $serializer;
        $this->helper = $helper;
        $this->transferFactory = $transferFactory;
        $this->client = $client;
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
            $paymentMethodEnabled = $this->config->isEnabled();
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Checking if Sezzle payment method is enabled',
                'payment_method_enabled' => $paymentMethodEnabled
            ]);
            if (!$paymentMethodEnabled) {
                return false;
            }
            $expressEnabled = $this->config->isExpressEnabled();
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Checking if Sezzle express is enabled',
                'express_enabled' => $expressEnabled
            ]);
            if (!$expressEnabled) {
                return false;
            }
            $expressCheckoutFeatureFlagEnabled = $this->config->isExpressCheckoutFeatureFlagEnabled();
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Checking if Sezzle express checkout feature flag is enabled',
                'express_checkout_feature_flag_enabled' => $expressCheckoutFeatureFlagEnabled
            ]);
            if (!$expressCheckoutFeatureFlagEnabled) {
                return false;
            }

            if (!$this->hasItemsInCart()) {
                return false;
            }
            return true;
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
