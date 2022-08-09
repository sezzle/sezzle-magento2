<?php

namespace Sezzle\Sezzlepay\Plugin\Sales\Block\Adminhtml\Order\View;

use Magento\Sales\Block\Adminhtml\Order\View\Info;

class InfoPlugin
{
    /**
     * @param Info $subject
     * @param string $result
     *
     * @return string
     */
    public function afterToHtml(Info $subject, string $result): string
    {
        $sezzleOrderInfoBlock = $subject->getChildBlock('sezzle_order_info');
        if (!$sezzleOrderInfoBlock || $subject->getNameInLayout() !== 'order_info') {
            return $result;
        }

        $sezzleOrderInfoBlock->setTemplate('Sezzle_Sezzlepay::order/view/info.phtml');
        return $result . $sezzleOrderInfoBlock->toHtml();
    }
}
