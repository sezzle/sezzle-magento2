<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Minicart;

use Magento\Checkout\Model\CompositeConfigProvider;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Sezzle\Sezzlepay\Helper\Data as Helper;
use Sezzle\Sezzlepay\Gateway\Config\Config;

/**
 * Class ExpressCheckout
 * @package Sezzle\Sezzlepay\Block\Minicart
 */
class ExpressCheckout extends Template
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var CompositeConfigProvider
     */
    private $configProvider;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * ExpressCheckout constructor.
     * @param Context $context
     * @param Helper $helper
     * @param Config $config
     * @param CheckoutSession $checkoutSession
     * @param Cart $cart
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $quoteRepository
     * @param CompositeConfigProvider $configProvider
     * @param SerializerInterface $serializer
     * @param CustomerSession $customerSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        Helper $helper,
        Config $config,
        CheckoutSession $checkoutSession,
        Cart $cart,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $quoteRepository,
        CompositeConfigProvider $configProvider,
        SerializerInterface $serializer,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->cart = $cart;
        $this->cartManagement = $cartManagement;
        $this->quoteRepository = $quoteRepository;
        $this->configProvider = $configProvider;
        $this->serializer = $serializer;
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * Check if Sezzle payment button should be displayed
     * Note: Cart items check is handled in JavaScript via customer-data subscription
     *
     * @return bool
     */
    public function canDisplay(): bool
    {
        try {
            $isEnabled = $this->config->isEnabled();
            $quote = $this->checkoutSession->getQuote();
            $hasItems = $quote->getItemsCount() > 0;
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'is_enabled' => $isEnabled,
                'has_items' => $hasItems,
                'quote' => [
                    'id' => $quote->getId(),
                    'items_count' => $quote->getItemsCount(),
                    'items_qty' => $quote->getItemsQty(),
                    'grand_total' => $quote->getGrandTotal(),
                    'customer_id' => $quote->getCustomerId(),
                    'store_id' => $quote->getStoreId()
                ]
            ]);
            return $isEnabled && $hasItems;
        } catch (NoSuchEntityException|InputException $e) {
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
