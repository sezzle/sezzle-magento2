<?php


namespace Sezzle\Payment\Controller\Payment;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Sezzle\Payment\Controller\AbstractController\Sezzle;

/**
 * Class Tokenize
 * @package Sezzle\Payment\Controller\Payment
 */
class Tokenize extends Sezzle
{
    /**
     * @return ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $customerID = $this->getRequest()->getParam('customer_id');
        try {
            $redirect = 'checkout/cart';
            $this->tokenize->saveTokenizeRecord($customerID);
            $this->messageManager->addSuccessMessage("Sezzle has successfully tokenized your account.");
        } catch (\Exception $e) {
            $this->sezzleHelper->logSezzleActions("Tokenize2 Exception: " . $e->getMessage());
            $this->messageManager->addErrorMessage(
                "Unable to tokenize your account."
            );
        }
        $this->_redirect($redirect);
    }
}
