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
        $redirectPath = 'checkout/cart';
        try {
            $quote = $this->checkoutSession->getQuote();
            $this->helper->logSezzleActions("Returned from Sezzle.");
            if ($customerUUID = $this->request->getParam('customer-uuid')) {
                $this->helper->logSezzleActions("****Start Tokenize record save****");
                $this->helper->logSezzleActions("Customer UUID : $customerUUID");
                $this->tokenize->saveTokenizeRecord($quote);
                $this->helper->logSezzleActions("****Start Tokenize record end****");
            }

            $cartManager = $this->customerSession->isLoggedIn() ? self::CART_MANAGER : self::GUEST_CART_MANAGER;
            $quoteId = $quote->getId();
            if ($cartManager === self::GUEST_CART_MANAGER) {
                $quoteId = $this->quoteIdToMaskedQuoteIdInterface->execute($quoteId);
            }

            $orderId = $this->$cartManager->placeOrder($quoteId);
            if (!$orderId) {
                throw new CouldNotSaveException(__("Unable to place the order."));
            }
            $redirectPath = 'checkout/onepage/success';
        } catch (CouldNotSaveException|NoSuchEntityException|LocalizedException $e) {
            $this->handleException($e);
        }

        return $this->resultRedirectFactory->create()->setPath($redirectPath);
    }

    /**
     * Handling Exception
     *
     * @param mixed $exc
     */
    private function handleException($exc)
    {
        $this->helper->logSezzleActions("Sezzle Transaction Exception: " . $exc->getMessage());
        $this->messageManager->addErrorMessage(
            $exc->getMessage()
        );
    }
}
