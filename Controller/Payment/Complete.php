<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Controller\Payment;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Sezzle\Sezzlepay\Controller\AbstractController\Sezzle;

/**
 * Class Complete
 * @package Sezzle\Sezzlepay\Controller\Payment
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

            $orderId = $this->orderManagement->placeOrder($quote->getId());
            if (!$orderId) {
                throw new CouldNotSaveException(__("Unable to place the order."));
            }
            $redirect = 'checkout/onepage/success';
        } catch (CouldNotSaveException $e) {
            $this->sezzleHelper->logSezzleActions("Sezzle Transaction Exception: " . $e->getMessage());
            $this->messageManager->addErrorMessage(
                $e->getMessage()
            );
        } catch (NoSuchEntityException $e) {
            $this->sezzleHelper->logSezzleActions("Sezzle Transaction Exception: " . $e->getMessage());
            $this->messageManager->addErrorMessage(
                $e->getMessage()
            );
        } catch (NotFoundException $e) {
            $this->sezzleHelper->logSezzleActions("Sezzle Transaction Exception: " . $e->getMessage());
            $this->messageManager->addErrorMessage(
                $e->getMessage()
            );
        } catch (LocalizedException $e) {
            $this->sezzleHelper->logSezzleActions("Sezzle Transaction Exception: " . $e->getMessage());
            $this->messageManager->addErrorMessage(
                $e->getMessage()
            );
        }
        return $this->_redirect($redirect);
//            $orderId = $quote->getReservedOrderId();
//            $this->sezzleHelper->logSezzleActions("Order ID from quote : $orderId.");
//
//            $this->checkoutSession
//                ->setLastQuoteId($quote->getId())
//                ->setLastSuccessQuoteId($quote->getId())
//                ->clearHelperData();
//            $this->sezzleHelper->logSezzleActions("Set data on checkout session");
//
//            $quote->collectTotals();
//            /** @var Order $order */
//            $order = $this->quoteManagement->submit($quote);
//            if ($order) {
//                $this->sezzleHelper->logSezzleActions("Order created");
//                $this->checkoutSession->setLastOrderId($order->getId())
//                    ->setLastRealOrderId($order->getIncrementId())
//                    ->setLastOrderStatus($order->getStatus());
//                // send email
//                try {
//                    $this->orderSender->send($order);
//                } catch (\Exception $e) {
//                    $this->sezzleHelper->logSezzleActions("Transaction Email Sending Error: ");
//                    $this->sezzleHelper->logSezzleActions($e->getMessage());
//                }
//
//                $this->messageManager->addSuccessMessage("Sezzle transaction has been completed successfully.");
//                $redirect = 'checkout/onepage/success';
//            }
//        } catch (LocalizedException $e) {
//            $this->sezzleHelper->logSezzleActions("Transaction Exception: " . $e->getMessage());
//            $this->messageManager->addErrorMessage(
//                $e->getMessage()
//            );
//        } catch (\Exception $e) {
//            $this->sezzleHelper->logSezzleActions("Transaction Exception: " . $e->getMessage());
//            $this->messageManager->addErrorMessage(
//                $e->getMessage()
//            );
//        }
//        if ($this->getRequest()->getParam('tokenize_checkout')) {
//            $url = $this->_url->getUrl($redirect);
//            $json = $this->jsonHelper->jsonEncode(["redirectURL" => $url]);
//            $jsonResult = $this->resultJsonFactory->create();
//            $jsonResult->setData($json);
//            return $jsonResult;
//        }
//        return $this->_redirect($redirect);
    }
}
