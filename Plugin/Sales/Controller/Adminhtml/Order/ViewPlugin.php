<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Plugin\Sales\Controller\Adminhtml\Order;

use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Sales\Controller\Adminhtml\Order\View;
use Sezzle\Payment\Model\Sezzle;
use Magento\Sales\Model\Order;

class ViewPlugin extends View
{


    /**
     * Save invoice
     *
     * @param View $subject
     * @param \Closure $proceed
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function aroundExecute(
        View $subject,
        \Closure $proceed
    ) {
        $order = $this->_initOrder();
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($order) {
            try {
                $currentTimestamp = (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp();
                $authExpiry = $order->getPayment()->getAdditionalInformation(Sezzle::SEZZLE_AUTH_EXPIRY);
                $authExpiryTimestamp =  (new \DateTime($authExpiry, new \DateTimeZone('UTC')))->getTimestamp();
                if ($authExpiryTimestamp < $currentTimestamp) {
                    $order->setActionFlag(Order::ACTION_FLAG_INVOICE, false);
                }
                $resultPage = $this->_initAction();
                $resultPage->getConfig()->getTitle()->prepend(__('Orders'));
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addErrorMessage(__('Exception occurred during order load'));
                $resultRedirect->setPath('sales/order/index');
                return $resultRedirect;
            }
            $resultPage->getConfig()->getTitle()->prepend(sprintf("#%s", $order->getIncrementId()));
            return $resultPage;
        }
        $resultRedirect->setPath('sales/*/');
        return $resultRedirect;
    }
}
