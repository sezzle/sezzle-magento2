<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Config\Container;

class ProductWidgetIdentity extends Container implements ProductWidgetConfigInterface
{
    const XML_PATH_TARGET_XPATH = 'product/sezzlepay/xpath';
    const XML_PATH_PAYMENT_ACTIVE = 'payment/sezzlepay/active';
    const XML_PATH_RENDER_TO_PATH = 'product/sezzlepay/render_x_path';
    const XML_PATH_FORCED_SHOW = 'product/sezzlepay/forced_show';
    const XML_PATH_ALIGNMENT = 'product/sezzlepay/alignment';
    const XML_PATH_THEME = 'product/sezzlepay/theme';
    const XML_PATH_WIDTH_TYPE = 'product/sezzlepay/width_type';
    const XML_PATH_IMAGE_URL = 'product/sezzlepay/image_url';
    const XML_PATH_HIDE_CLASS = 'product/sezzlepay/hide_classes';

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
            $this->getStore()->getStoreId(),
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
            $this->getStore()->getStoreId(),
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
            $this->getStore()->getStoreId(),
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
            $this->getStore()->getStoreId(),
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
            $this->getStore()->getStoreId(),
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
            $this->getStore()->getStoreId(),
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
            $this->getStore()->getStoreId(),
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
            $this->getStore()->getStoreId(),
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
        return !empty($data) ? explode('|', $data) : '';
    }
}
