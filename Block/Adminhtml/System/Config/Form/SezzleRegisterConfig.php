<?php
namespace Sezzle\Payment\Block\Adminhtml\System\Config\Form;

class SezzleRegisterConfig extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * Render element value
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = $this->_layout
            ->createBlock(\Sezzle\Payment\Block\Adminhtml\System\Config\SezzleRegisterAdmin::class)
            ->setTemplate('Sezzle_Payment::system/config/sezzle_register_admin.phtml')
            ->setCacheable(false)
            ->toHtml();

        return $html;
    }
}
