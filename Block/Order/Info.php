<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sezzle\Payment\Block\Order;

use Magento\Sales\Model\Order\Address;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Sezzle\Payment\Block\Adminhtml\Customer\Edit\Tab\Sezzle;

/**
 * Invoice view  comments form
 *
 * @api
 * @author      Magento Core Team <core@magentocommerce.com>
 * @since 100.0.2
 */
class Info extends \Magento\Sales\Block\Order\Info
{
    /**
     * @var string
     */
    protected $_template = 'Sezzle_Payment::order/sezzle_order_reference.phtml';

    public function getSezzleOrderReferenceID()
    {
        return $this->getOrder()->getPayment()->getAdditionalInformation(\Sezzle\Payment\Model\Sezzle::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID);
    }

    public function isSezzleOrder()
    {
        return $this->getOrder()->getPayment()->getMethod();
    }
}
