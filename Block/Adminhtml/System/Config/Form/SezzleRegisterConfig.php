<?php
/**
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Sezzle\Sezzlepay\Block\Adminhtml\System\Config\SezzleRegisterAdmin;

class SezzleRegisterConfig extends Field
{

    /**
     * Render element value
     *
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function render(AbstractElement $element)
    {
        return $this->_layout
            ->createBlock(SezzleRegisterAdmin::class)
            ->setTemplate('Sezzle_Sezzlepay::system/config/sezzle_register_admin.phtml')
            ->setCacheable(false)
            ->toHtml();
    }
}
