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
use Sezzle\Sezzlepay\Gateway\Http\TransferFactory;
use Sezzle\Sezzlepay\Gateway\Http\Client;

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
     * @param Helper $helper
     * @param Config $config
     * @param CheckoutSession $checkoutSession
     * @param Cart $cart
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $quoteRepository
     * @param CompositeConfigProvider $configProvider
     * @param SerializerInterface $serializer
     * @param CustomerSession $customerSession
     * @param TransferFactory $transferFactory
     * @param Client $client
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
        TransferFactory $transferFactory,
        Client $client,
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
        $this->transferFactory = $transferFactory;
        $this->client = $client;
        parent::__construct($context, $data);
    }

    /**
     * Check if Sezzle payment is enabled
     * Note: Cart items check is handled in JavaScript via customer-data subscription
     *
     * @return bool
     */
    public function canDisplay(): bool
    {
        try {
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Checking if Sezzle payment is enabled',
                'is_enabled' => $this->config->isEnabled()
            ]);

            if (!$this->config->isEnabled() || !$this->config->isExpressEnabled()) {
                return false;
            }

            // Check feature flag
            $featureFlag = $this->getFeatureFlag($this->config->getExpressCheckoutFeatureFlag());

            return $featureFlag;
        } catch (NoSuchEntityException|InputException $e) {
            return false;
        }
    }

    /**
     * Get feature flag from Sezzle gateway
     *
     * @param string $featureFlag
     * @return bool|null
     */
    private function getFeatureFlag(string $featureFlag): ?bool
    {
        try {
            $storeId = $this->_storeManager->getStore()->getId();
            $uri = $this->config->getGatewayURL($storeId) . '/feature-flags/' . $featureFlag;

            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Fetching feature flag',
                'feature_flag' => $featureFlag,
                'uri' => $uri
            ]);

            $transferO = $this->transferFactory->createWithBasicAuth([
                '__store_id' => $storeId,
                '__method' => Client::HTTP_GET,
                '__uri' => $uri
            ]);

            $response = $this->client->placeRequest($transferO);

            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Feature flag response received',
                'response' => $response
            ]);

            return $response;
        } catch (\Exception $e) {
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Error fetching feature flag: ' . $e->getMessage(),
                'exception' => get_class($e)
            ]);
            return null;
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
        try {
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Getting checkout config'
            ]);

            // Ensure a quote exists in the session before getting config
            $quote = $this->checkoutSession->getQuote();

            // If quote doesn't have an ID, it means it hasn't been saved yet
            if (!$quote->getId()) {
                $this->helper->logSezzleActions([
                    'log_origin' => __METHOD__,
                    'message' => 'Quote does not exist, creating new quote'
                ]);

                // For guest customers, create and save the quote
                if (!$this->customerSession->isLoggedIn()) {
                    $quote->setIsActive(true);
                    $quote->setStoreId($this->_storeManager->getStore()->getId());
                    $this->quoteRepository->save($quote);
                } else {
                    // For logged-in customers, create cart via cart management
                    try {
                        $customerId = $this->customerSession->getCustomerId();
                        $quote = $this->cartManagement->getCartForCustomer($customerId);
                    } catch (\Exception $e) {
                        // If that fails, just save the current quote
                        $quote->setCustomerId($this->customerSession->getCustomerId());
                        $quote->setIsActive(true);
                        $quote->setStoreId($this->_storeManager->getStore()->getId());
                        $this->quoteRepository->save($quote);
                    }
                }

                $this->helper->logSezzleActions([
                    'log_origin' => __METHOD__,
                    'message' => 'Quote created successfully',
                    'quote_id' => $quote->getId()
                ]);
            }

            $config = $this->configProvider->getConfig();
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Checkout config retrieved successfully',
                'has_sezzle_config' => isset($config['payment']['sezzlepay'])
            ]);
            return $config;
        } catch (\Exception $e) {
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Error getting checkout config: ' . $e->getMessage(),
                'exception' => get_class($e)
            ]);
            return [];
        }
    }

    /**
     * Retrieve serialized checkout config
     *
     * @return string
     */
    public function getSerializedCheckoutConfig(): string
    {
        try {
            $config = $this->getCheckoutConfig();
            $serialized = $this->serializer->serialize($config);
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Config serialized successfully',
                'length' => strlen($serialized)
            ]);
            return $serialized;
        } catch (\Exception $e) {
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Error serializing config: ' . $e->getMessage(),
                'exception' => get_class($e)
            ]);
            throw $e;
        }
    }
}
