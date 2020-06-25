<?php
/**
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Block\Adminhtml\System\Config\Form;


use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SezzleWidgetInfo extends Field
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
        $output = '<div class="deprecated-message">';
        $output .= '<div class="comment">';
        $output .= __("Make sure to put <code>&ltdiv id='sezzle-widget'/&gt</code> after the price element in the PDP and Cart theme files
                        once you have enabled the below options.");
        $output .= "</div></div>";
        return $output;
    }

}
