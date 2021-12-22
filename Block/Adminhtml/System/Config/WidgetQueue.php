<?php

namespace Sezzle\Sezzlepay\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Sezzle\Sezzlepay\Model\System\Config\Container\SezzleConfigInterface;



class WidgetQueue extends Field
{
    const SEZZLE_AUTH_ENDPOINT = "/widget/queue";

    private $sezzleConfig;
    protected $_template = 'Sezzle_Sezzlepay::system/config/widgetqueue.phtml';


    public function __construct(
        Context $context,
        SezzleConfigInterface $sezzleConfig,
        array $data = []
    ) {
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

    public function getAjaxUrl()
    {
        $storeId = $this->sezzleConfig->getStore()->getStoreId();
        return $this->sezzleConfig->getSezzleBaseUrl($storeId) . self::SEZZLE_AUTH_ENDPOINT;
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


