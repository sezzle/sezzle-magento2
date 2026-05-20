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
use Magento\Quote\Api\Data\CartInterface;
use Sezzle\Sezzlepay\Controller\AbstractController\Sezzle;
use Throwable;

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
        $quote = null;
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
            $this->handleException($e, $quote);
        }

        return $this->resultRedirectFactory->create()->setPath($redirectPath);
    }

    /**
     * Log the failure, release the Sezzle auth so funds aren't held until
     * expiry, and surface the error to the customer. Recovery itself is
     * best-effort — its own failure must not mask the original error.
     */
    private function handleException(Throwable $exc, ?CartInterface $quote): void
    {
        $this->helper->logSezzleActions("Sezzle Transaction Exception: " . $exc->getMessage());

        if ($quote !== null && $quote->getId()) {
            try {
                $this->sessionRecovery->release($quote);
                $this->cartRepository->save($quote);
            } catch (Throwable $recoveryExc) {
                $this->helper->logSezzleActions(
                    "Sezzle release-on-failure recovery error: " . $recoveryExc->getMessage()
                );
            }
        }

        $this->messageManager->addErrorMessage($exc->getMessage());
    }
}
