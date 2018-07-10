<?php
namespace Sezzle\Sezzlepay\Controller\Standard;

class Complete extends \Sezzle\Sezzlepay\Controller\Sezzlepay
{
    public function execute()
    {
        $redirect = 'checkout/cart';
        try {
            $this->_logger->debug("Returned from Sezzlepay.");
            $quote = $this->_checkoutSession->getQuote();
            $quoteId = $quote->getId();
            $payment = $quote->getPayment();
            $reference = $payment->getAdditionalInformation(\Sezzle\Sezzlepay\Model\SezzlePaymentMethod::ADDITIONAL_INFORMATION_KEY_ORDERID);
            $orderId = $quote->getReservedOrderId();
            $this->_logger->debug("Order ID from quote $orderId.");
            // Capture this payment
            $response = $this->getSezzlepayModel()->capturePayment($reference);
            $this->_logger->debug("Response received from Sezzle.");

            $this->_checkoutSession
                ->setLastQuoteId($quote->getId())
                ->setLastSuccessQuoteId($quote->getId())
                ->clearHelperData();
            $this->_logger->debug("Set data on checkout session");
            
            $order = $this->_quoteManagement->submit($quote);
            $this->_logger->debug("Order created");
            $newOrderId = $order->getId();
            $order->setEmailSent(0);
            $this->_logger->debug("Sent email");
            if ($order) {
                $this->_checkoutSession->setLastOrderId($order->getId())
                                   ->setLastRealOrderId($order->getIncrementId())
                                   ->setLastOrderStatus($order->getStatus());
                $this->_createTransaction($order, $reference);
                $this->_logger->debug("Created transaction with reference $reference");
                $this->messageManager->addSuccess("Sezzlepay Transaction Completed");
                $redirect = 'checkout/onepage/success';
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_logger->debug("Transaction Exception: " . $e->getMessage());
            $this->messageManager->addError(
                $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->_logger->debug("Transaction Exception: " . $e->getMessage());
            $this->messageManager->addError(
                $e->getMessage()
            );
        }
        $this->_redirect($redirect);
    }
}
