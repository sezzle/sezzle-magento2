<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Config\Container;

class CartWidgetIdentity extends Container implements CartWidgetConfigInterface
{
    const XML_PATH_TARGET_XPATH = 'cart/sezzlepay/xpath';
    const XML_PATH_PAYMENT_ACTIVE = 'payment/sezzlepay/active';
    const XML_PATH_RENDER_TO_PATH = 'cart/sezzlepay/render_x_path';
    const XML_PATH_FORCED_SHOW = 'cart/sezzlepay/forced_show';
    const XML_PATH_ALIGNMENT = 'cart/sezzlepay/alignment';
    const XML_PATH_THEME = 'cart/sezzlepay/theme';
    const XML_PATH_WIDTH_TYPE = 'cart/sezzlepay/width_type';
    const XML_PATH_IMAGE_URL = 'cart/sezzlepay/image_url';
    const XML_PATH_HIDE_CLASS = 'cart/sezzlepay/hide_classes';

    /**
     * @inheritdoc
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PAYMENT_ACTIVE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @inheritdoc
     */
    public function getTargetXPath()
    {
        $data = $this->getConfigValue(
            self::XML_PATH_TARGET_XPATH,
            $this->getStore()->getWebsiteId(),
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
        return !empty($data) ? explode('|', $data) : '';
    }

    /**
     * @inheritdoc
     */
    public function getRenderToPath()
    {
        $data = $this->getConfigValue(
            self::XML_PATH_RENDER_TO_PATH,
            $this->getStore()->getWebsiteId(),
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
        return !empty($data) ? explode('|', $data) : '';
    }

    /**
     * @inheritdoc
     */
    public function getForcedShow()
    {
        return $this->getConfigValue(
            self::XML_PATH_FORCED_SHOW,
            $this->getStore()->getWebsiteId(),
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @inheritdoc
     */
    public function getAlignment()
    {
        return $this->getConfigValue(
            self::XML_PATH_ALIGNMENT,
            $this->getStore()->getWebsiteId(),
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @inheritdoc
     */
    public function getTheme()
    {
        return $this->getConfigValue(
            self::XML_PATH_THEME,
            $this->getStore()->getWebsiteId(),
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidthType()
    {
        return $this->getConfigValue(
            self::XML_PATH_WIDTH_TYPE,
            $this->getStore()->getWebsiteId(),
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @inheritdoc
     */
    public function getImageUrl()
    {
        return $this->getConfigValue(
            self::XML_PATH_IMAGE_URL,
            $this->getStore()->getWebsiteId(),
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @inheritdoc
     */
    public function getHideClass()
    {
        $data = $this->getConfigValue(
            self::XML_PATH_HIDE_CLASS,
            $this->getStore()->getWebsiteId(),
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
        return !empty($data) ? explode('|', $data) : '';
    }
}
