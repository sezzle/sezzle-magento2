<?php

namespace Sezzle\Sezzlepay\Controller\Standard;

use Sezzle\Sezzlepay\Controller\AbstractController\SezzlePay;

/**
 * Class Complete
 * @package Sezzle\Sezzlepay\Controller\Standard
 */
class Complete extends SezzlePay
{
    /**
     * Complete the order
     */
    public function execute()
    {
        $redirect = 'checkout/cart';
        try {
            $this->_logger->debug("Returned from Sezzlepay.");
            $quote = $this->_checkoutSession->getQuote();
            $payment = $quote->getPayment();
            $reference = $payment->getAdditionalInformation(\Sezzle\Sezzlepay\Model\SezzlePay::ADDITIONAL_INFORMATION_KEY_ORDERID);
            $orderId = $quote->getReservedOrderId();
            $this->_logger->debug("Order ID from quote $orderId.");
            // Capture this payment
            $response = $this->_sezzlepayModel->capturePayment($reference);
            $this->_logger->debug("Response received from Sezzle.");

            $this->_checkoutSession
                ->setLastQuoteId($quote->getId())
                ->setLastSuccessQuoteId($quote->getId())
                ->clearHelperData();
            $this->_logger->debug("Set data on checkout session");

            $order = $this->_quoteManagement->submit($quote);
            $this->_logger->debug("Order created");

            if ($order) {
                $this->_checkoutSession->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());
                $this->_sezzlepayModel->createTransaction($order, $reference);
                $this->_logger->debug("Created transaction with reference $reference");

                // send email
                try {
                    $this->_orderSender->send($order);
                } catch (\Exception $e) {
                    $this->_helper->debug("Transaction Email Sending Error: " . json_encode($e));
                }

                $this->_messageManager->addSuccess("Sezzlepay Transaction Completed");
                $redirect = 'checkout/onepage/success';
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_logger->debug("Transaction Exception: " . $e->getMessage());
            $this->_messageManager->addError(
                $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->_logger->debug("Transaction Exception: " . $e->getMessage());
            $this->_messageManager->addError(
                $e->getMessage()
            );
        }
        $this->redirect($redirect);
    }
}
