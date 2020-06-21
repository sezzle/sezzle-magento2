<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Controller\Payment;

use Sezzle\Payment\Controller\AbstractController\Sezzle;

/**
 * Class Complete
 * @package Sezzle\Payment\Controller\Payment
 */
class Complete extends Sezzle
{
    /**
     * Complete the order
     */
    public function execute()
    {
        $redirect = 'checkout/cart';
        try {
            $quote = $this->_checkoutSession->getQuote();
            $this->sezzleHelper->logSezzleActions("Returned from Sezzle.");
            if ($customerUUID = $this->getRequest()->getParam('customer-uuid')) {
                $this->tokenize->saveTokenizeRecord($quote);
            }
            $payment = $quote->getPayment();
            $reference = $payment->getAdditionalInformation(
                \Sezzle\Payment\Model\Sezzle::ADDITIONAL_INFORMATION_KEY_REFERENCE_ID
            );
            $orderId = $quote->getReservedOrderId();
            $this->sezzleHelper->logSezzleActions("Order ID from quote : $orderId.");

            $this->_checkoutSession
                ->setLastQuoteId($quote->getId())
                ->setLastSuccessQuoteId($quote->getId())
                ->clearHelperData();
            $this->sezzleHelper->logSezzleActions("Set data on checkout session");

            $quote->collectTotals();
            $order = $this->_quoteManagement->submit($quote);
            $this->sezzleHelper->logSezzleActions("Order created");

            if ($order) {
                $this->_checkoutSession->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());
                $this->sezzleHelper->logSezzleActions("Created transaction with reference $reference");

                // send email
                try {
                    $this->_orderSender->send($order);
                } catch (\Exception $e) {
                    $this->_helper->debug("Transaction Email Sending Error: " . json_encode($e));
                }

                $this->messageManager->addSuccessMessage("Sezzle transaction has been completed successfully.");
                $redirect = 'checkout/onepage/success';
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->sezzleHelper->logSezzleActions("Transaction Exception: " . $e->getMessage());
            $this->messageManager->addErrorMessage(
                $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions("Transaction Exception: " . $e->getMessage());
            $this->messageManager->addErrorMessage(
                $e->getMessage()
            );
        }
        $this->_redirect($redirect);
    }
}
