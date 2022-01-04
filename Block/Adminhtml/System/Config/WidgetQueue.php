<?php

namespace Sezzle\Sezzlepay\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\Store;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleIdentity;



class WidgetQueue extends Field
{
    const SEZZLE_AUTH_ENDPOINT = "/widget/queue";

    private $sezzleConfig;
    protected $_template = 'Sezzle_Sezzlepay::system/config/widgetqueue.phtml';
    protected $storeManager;
    protected $store;

    public function __construct(
        Context $context,
        SezzleConfigInterface $sezzleConfig,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        $this->sezzleConfig = $sezzleConfig;
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getStore()
    {
        //current store
        if ($this->store instanceof Store) {
            return $this->store;
        }
        return $this->storeManager->getStore();
    }

    public function getAjaxUrl()
    {
        $storeId = $this->sezzleConfig->getStore()->getStoreId();
        return $this->sezzleConfig->getSezzleBaseUrl($storeId, SezzleIdentity::API_VERSION_V1) . self::SEZZLE_AUTH_ENDPOINT;
    }

    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'widget_queue',
                'label' => __('Request'),
            ]
        );

        return $button->toHtml();
    }
}
