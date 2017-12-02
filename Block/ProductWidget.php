<?php
namespace Sezzle\Sezzlepay\Block;

use Magento\Framework\View\Element\Template;

class ProductWidget extends Template
{
    protected $_scopeConfig;

    public function __construct(
        Template\Context $context,
        array $data
    ) {
        $this->_scopeConfig = $context->getScopeConfig();
        parent::__construct($context, $data);
    }

    public function getJsConfig() {
        $alignment = $this->_scopeConfig->getValue('product/sezzlepay/alignment', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $targetXPath = explode('|', $this->_scopeConfig->getValue('product/sezzlepay/xpath', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE));
        $renderToPath = explode('|', $this->_scopeConfig->getValue('product/sezzlepay/render_x_path', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE));
        $forcedShow = $this->_scopeConfig->getValue('product/sezzlepay/forced_show', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE) == "1" ? true : false;
        $alignment = $this->_scopeConfig->getValue('product/sezzlepay/alignment', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $merchantID = $this->_scopeConfig->getValue('payment/sezzlepay/merchant_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $theme = $this->_scopeConfig->getValue('product/sezzlepay/theme', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $widthType = $this->_scopeConfig->getValue('product/sezzlepay/width-type', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $imageUrl = $this->_scopeConfig->getValue('product/sezzlepay/image-url', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $hideClasses = explode('|', $this->_scopeConfig->getValue('product/sezzlepay/hide-classes', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE));

        return array(
            'targetXPath'          => $targetXPath,
            'renderToPath'         => $renderToPath,
            'forcedShow'           => $forcedShow,
            'alignment'            => $alignment,
            'merchantID'           => $merchantID,
            'theme'                => $theme,
            'widthType'            => $widthType,
            'widgetType'           => 'product-page',
            'minPrice'             => 0,
            'maxPrice'             => 100000,
            'imageUrl'             => $imageUrl,
            'hideClasses'          => $hideClasses,
        );
    }
}