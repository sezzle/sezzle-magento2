<?php
namespace Sezzle\Payment\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Sezzle\Payment\Block\Adminhtml\System\Config\SezzleRegisterAdmin;

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
            ->setTemplate('Sezzle_Payment::system/config/sezzle_register_admin.phtml')
            ->setCacheable(false)
            ->toHtml();
    }
}
