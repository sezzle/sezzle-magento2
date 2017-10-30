<?php
namespace Sezzle\Sezzlepay\Controller\Standard;
class Complete extends \Sezzle\Sezzlepay\Controller\Sezzlepay
{
    public function execute()
    {
        try {
            $tranId = $this->getRequest()->getParam('id');
            $this->_logger->info("Transaction id received : $tranId");
            $sezzleId = $this->getRequest()->getQuery('x_gateway_reference');
            $this->_logger->info("Sezzle Id received : $sezzleId");
            // Get the order id from the request url
            $orderTranId = explode('-', $tranId);
            $transactionId = $orderTranId[0];
            $orderId = $orderTranId[1];
            $order = $this->getOrderById($orderId);

            // Sanity check
            if ($order->getState() == \Magento\Sales\Model\Order::STATE_PROCESSING) {
                $this->getResponse()->setRedirect(
                    $this->_url->getUrl('checkout/cart')
                );
            }

            if ($order->canInvoice()) {
                $this->_logger->info("Can invoice");
                $this->updatePayment($order, $sezzleId);
                $this->_logger->info("Updated payment");
                $order->setState($order::STATE_PROCESSING)
                    ->setStatus($this->_salesOrderConfig->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PROCESSING));
                $order->addStatusHistoryComment(__('Payment approved by Sezzlepay'));
                $this->_logger->info("Set order processing state");
                // Create invoice
                $this->createInvoice($order);

                // Redirect to success
                $this->getResponse()->setRedirect(
                    $this->_url->getUrl('checkout/onepage/success')
                );
            } else {
                $this->_logger->info("Could not create invoice");
                $this->messageManager->addError(
                    __('Sezzlepay payment cannot be processed. Please contact the administrator.')
                );
                $this->getResponse()->setRedirect(
                    $this->_url->getUrl('checkout/cart')
                );
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_logger->info("LocalizedException : $e");
            $this->messageManager->addError(
                $e->getMessage()
            );
            $this->getResponse()->setRedirect(
                $this->_url->getUrl('checkout/cart')
            );
        } catch (\Exception $e) {
            $this->_logger->info("Exception : $e");
            $this->messageManager->addError(
                $e->getMessage()
            );
            $this->getResponse()->setRedirect(
                $this->_url->getUrl('checkout/cart')
            );
        }
    }
}