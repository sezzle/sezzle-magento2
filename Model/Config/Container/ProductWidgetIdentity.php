<?php
/**
 * Created by PhpStorm.
 * User: arijit
 * Date: 1/27/2019
 * Time: 4:06 PM
 */

namespace Sezzle\Sezzlepay\Model\Config\Container;


class ProductWidgetIdentity extends Container implements ProductWidgetConfigInterface
{
    const XML_PATH_TARGET_XPATH = 'product/sezzlepay/xpath';
    const XML_PATH_RENDER_TO_PATH = 'product/sezzlepay/render_x_path';
    const XML_PATH_FORCED_SHOW = 'product/sezzlepay/forced_show';
    const XML_PATH_ALIGNMENT = 'product/sezzlepay/alignment';
    const XML_PATH_THEME = 'product/sezzlepay/theme';
    const XML_PATH_WIDTH_TYPE = 'product/sezzlepay/width_type';
    const XML_PATH_IMAGE_URL = 'product/sezzlepay/image_url';
    const XML_PATH_HIDE_CLASS = 'product/sezzlepay/hide_classes';
    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_EMAIL_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * @return mixed
     */
    public function getTargetXPath()
    {
        $data = $this->getConfigValue(
            self::XML_PATH_TARGET_XPATH,
            $this->getStore()->getStoreId(),
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        if (!empty($data)) {
            return explode('|', $data);
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getRenderToPath()
    {
        $data = $this->getConfigValue(
            self::XML_PATH_RENDER_TO_PATH,
            $this->getStore()->getStoreId(),
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        if (!empty($data)) {
            return explode('|', $data);
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getForcedShow()
    {
        return $this->getConfigValue(
            self::XML_PATH_FORCED_SHOW,
            $this->getStore()->getStoreId(),
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return mixed
     */
    public function getAlignment()
    {
        return $this->getConfigValue(
            self::XML_PATH_ALIGNMENT,
            $this->getStore()->getStoreId(),
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return mixed
     */
    public function getTheme()
    {
        return $this->getConfigValue(
            self::XML_PATH_THEME,
            $this->getStore()->getStoreId(),
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return mixed
     */
    public function getWidthType()
    {
        return $this->getConfigValue(
            self::XML_PATH_WIDTH_TYPE,
            $this->getStore()->getStoreId(),
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return mixed
     */
    public function getImageUrl()
    {
        return $this->getConfigValue(
            self::XML_PATH_IMAGE_URL,
            $this->getStore()->getStoreId(),
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return mixed
     */
    public function getHideClass()
    {
        $data = $this->getConfigValue(
            self::XML_PATH_HIDE_CLASS,
            $this->getStore()->getStoreId(),
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        if (!empty($data)) {
            return explode('|', $data);
        }
        return false;
    }
}