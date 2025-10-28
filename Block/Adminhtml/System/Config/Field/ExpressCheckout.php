<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Gateway\Http\TransferFactory;
use Sezzle\Sezzlepay\Gateway\Http\Client;
use Sezzle\Sezzlepay\Helper\Data as Helper;

/**
 * Class ExpressCheckout
 * @package Sezzle\Sezzlepay\Block\Adminhtml\System\Config\Field
 */
class ExpressCheckout extends Field
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
     * @param Helper $helper
     * @param TransferFactory $transferFactory
     * @param Client $client
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        Helper $helper,
        TransferFactory $transferFactory,
        Client $client,
        array $data = []
    ) {
        $this->config = $config;
        $this->helper = $helper;
        $this->transferFactory = $transferFactory;
        $this->client = $client;
        parent::__construct($context, $data);
    }

    /**
     * Render Element - hide field if feature flag is not enabled
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if (!$this->isFeatureFlagEnabled()) {
            return '';
        }

        return parent::render($element);
    }

    /**
     * Check if feature flag is enabled
     *
     * @return bool
     */
    private function isFeatureFlagEnabled(): bool
    {
        try {
            $featureFlag = $this->getFeatureFlag($this->config->getExpressCheckoutFeatureFlag());

            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Checking feature flag for express checkout admin field',
                'feature_flag_response' => $featureFlag
            ]);

            return $featureFlag;
        } catch (\Exception $e) {
            $this->helper->logSezzleActions([
                'log_origin' => __METHOD__,
                'message' => 'Error checking feature flag: ' . $e->getMessage(),
                'exception' => get_class($e)
            ]);
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
}
