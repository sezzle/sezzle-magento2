<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Adminhtml\System\Config\Field;

use Magento\Framework\Registry;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;

/**
 * Fieldset renderer for Sezzle solution
 */
class Date extends Field
{
    /**
     * @var  Registry
     */
    protected $_coreRegistry;

    /**
     * @param Context  $context
     * @param Registry $coreRegistry
     * @param array    $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        //get configuration element
        $element->setDateFormat(\Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT);
        $element->setTimeFormat(null);
        return parent::render($element);
    }
}
