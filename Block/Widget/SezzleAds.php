<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Widget;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use Magento\Framework\View\Element\Template;
use Sezzle\Sezzlepay\Gateway\Command\AuthorizeCommand;
use Sezzle\Sezzlepay\Gateway\Config\Config;
use Sezzle\Sezzlepay\Helper\Data as Helper;
use Sezzle\Sezzlepay\Model\Ui\ConfigProvider;

/**
 * Sezzle Ads placement on the checkout success page
 */
class SezzleAds extends Template
{
    const SDK_URL = 'https://checkout-sdk.sezzle.com/sezzle-ads.js';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var JsonHexTag
     */
    private $json;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @param Template\Context $context
     * @param Config $config
     * @param CheckoutSession $checkoutSession
     * @param JsonHexTag $json
     * @param Helper $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config           $config,
        CheckoutSession  $checkoutSession,
        JsonHexTag       $json,
        Helper           $helper,
        array            $data = []
    )
    {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->json = $json;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Whether the placement should render: Sezzle Ads is on and a merchant UUID is set
     *
     * Independent of whether the Sezzle payment method is enabled, by Product's decision: the ad promotes the Sezzle
     * app rather than paying with Sezzle at this store, so it shows for every order whatever the payment method.
     * Whether Sezzle has enrolled the merchant is decided by the SDK and widget-server, not here.
     *
     * @return bool
     */
    public function isSezzleAdsEnabled(): bool
    {
        try {
            if (!$this->config->isSezzleAdsEnabled()) {
                return false;
            }
            if (!$this->config->getMerchantUUID()) {
                $this->helper->logSezzleActions('Sezzle Ads not rendered as Merchant UUID is empty');
                return false;
            }
            return true;
        } catch (NoSuchEntityException|InputException $e) {
            return false;
        }
    }

    /**
     * Whether this instance of the block sits where the merchant chose to place the ad
     *
     * The layout declares the block once per placement, each with a "placement" argument, and only the one
     * matching the configured placement renders.
     *
     * @return bool
     */
    public function isConfiguredPlacement(): bool
    {
        try {
            return $this->getData('placement') === $this->config->getSezzleAdsPlacement();
        } catch (NoSuchEntityException|InputException $e) {
            return false;
        }
    }

    /**
     * ID of this block's container, unique per placement, so its margin can be targeted
     *
     * @return string
     */
    public function getContainerId(): string
    {
        $suffix = (string)($this->getData('placement') ?: $this->getNameInLayout());

        return 'sezzle-ads-' . preg_replace('/[^a-z0-9_-]/i', '-', $suffix);
    }

    /**
     * CSS margin for the container, or an empty string for none
     *
     * @return string
     */
    public function getContainerMargin(): string
    {
        try {
            return $this->config->getSezzleAdsMargin();
        } catch (NoSuchEntityException|InputException $e) {
            return '';
        }
    }

    /**
     * @return string
     */
    public function getSdkUrl(): string
    {
        return self::SDK_URL;
    }

    /**
     * Config passed to SezzleAds.mountAll(), JSON encoded with <, >, & and quotes hex-escaped for inline script
     *
     * language is deliberately left out: the SDK reads <html lang>, which Magento sets from the store locale.
     *
     * @return string
     */
    public function getSdkConfigJson(): string
    {
        $sdkConfig = [];
        try {
            $sdkConfig['merchantUUID'] = (string)$this->config->getMerchantUUID();
            $sdkConfig['theme'] = $this->config->getSezzleAdsTheme();
        } catch (NoSuchEntityException|InputException $e) {
            // isSezzleAdsEnabled() resolved the same values a moment ago, so this is not expected
        }

        if ($orderUUID = $this->getSezzleOrderUUID()) {
            $sdkConfig['orderUUID'] = $orderUUID;
        }

        return $this->json->serialize($sdkConfig);
    }

    /**
     * Sezzle order UUID of the order just placed, or null when it was not paid with Sezzle
     *
     * @return string|null
     */
    private function getSezzleOrderUUID(): ?string
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $payment = $order ? $order->getPayment() : null;
        if (!$payment || $payment->getMethod() !== ConfigProvider::CODE) {
            return null;
        }

        $orderUUID = $payment->getAdditionalInformation(AuthorizeCommand::KEY_ORIGINAL_ORDER_UUID);

        return is_string($orderUUID) && $orderUUID !== '' ? $orderUUID : null;
    }
}
