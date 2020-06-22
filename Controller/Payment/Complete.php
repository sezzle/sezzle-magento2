<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Controller\Payment;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
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
            $quote = $this->checkoutSession->getQuote();
            $this->sezzleHelper->logSezzleActions("Returned from Sezzle.");
            if ($customerUUID = $this->getRequest()->getParam('customer-uuid')) {
                $this->sezzleHelper->logSezzleActions("****Start Tokenize record save****");
                $this->sezzleHelper->logSezzleActions("Customer UUID : $customerUUID");
                $this->tokenize->saveTokenizeRecord($quote);
                $this->sezzleHelper->logSezzleActions("****Start Tokenize record end****");
            }
            $orderId = $quote->getReservedOrderId();
            $this->sezzleHelper->logSezzleActions("Order ID from quote : $orderId.");

            $this->checkoutSession
                ->setLastQuoteId($quote->getId())
                ->setLastSuccessQuoteId($quote->getId())
                ->clearHelperData();
            $this->sezzleHelper->logSezzleActions("Set data on checkout session");

            $quote->collectTotals();
            /** @var Order $order */
            $order = $this->quoteManagement->submit($quote);
            if ($order) {
                $this->sezzleHelper->logSezzleActions("Order created");
                $this->checkoutSession->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());
                // send email
                try {
                    $this->orderSender->send($order);
                } catch (\Exception $e) {
                    $this->sezzleHelper->logSezzleActions("Transaction Email Sending Error: ");
                    $this->sezzleHelper->logSezzleActions($e->getMessage());
                }

                $this->messageManager->addSuccessMessage("Sezzle transaction has been completed successfully.");
                $redirect = 'checkout/onepage/success';
            }
        } catch (LocalizedException $e) {
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
