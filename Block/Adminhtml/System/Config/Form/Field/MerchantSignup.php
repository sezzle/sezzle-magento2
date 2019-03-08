<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Getting back the Klarna Merchant Onboarding text with link
 *
 * Class MerchantSignup
 * @package Sezzle\Sezzlepay\Block\Adminhtml\System\Config\Form\Field
 */
class MerchantSignup extends Field
{

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $url = "https://dashboard.sezzle.com/merchant/signup";
        if (empty($url)) {
            return parent::render($element);
        }

        $urlText = __('link');
        $urlTag = '<p style="display:inline"><a href="' . $url . '" target="_blank">' . $urlText . '</a></span>';

        $text = __('Click on this %1 to signup if you haven\'t signed up yet.', $urlTag);
        return $text;
    }
}
