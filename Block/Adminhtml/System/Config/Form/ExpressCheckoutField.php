<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Template\Context;

/**
 * Express Checkout Field - Only shows if public and private keys are set
 */
class ExpressCheckoutField extends Field
{
    /**
     * Render element HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $publicKey = $this->_scopeConfig->getValue(
            'payment/sezzlepay/public_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $privateKey = $this->_scopeConfig->getValue(
            'payment/sezzlepay/private_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        // Only render the field if both keys are set
        if (empty($publicKey) || empty($privateKey)) {
            return '<tr id="row_' . $element->getHtmlId() . '">
                        <td class="label"><label for="' . $element->getHtmlId() . '"><span>' . $element->getLabel() . '</span></label></td>
                        <td class="value">
                            <div class="message message-notice">
                                <div>Express Checkout is only available after you configure your Public Key and Private Key.</div>
                            </div>
                        </td>
                        <td class=""></td>
                    </tr>';
        }

        return parent::render($element);
    }
}
