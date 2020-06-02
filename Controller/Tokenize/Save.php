<?php


namespace Sezzle\Payment\Controller\Tokenize;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Sezzle\Payment\Controller\AbstractController\Sezzle;

/**
 * Class Save
 * @package Sezzle\Payment\Controller\Payment
 */
class Save extends Sezzle
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
            $this->sezzleHelper->logSezzleActions("Tokenize Exception: " . $e->getMessage());
            $this->messageManager->addErrorMessage(
                "Unable to tokenize your account."
            );
        }
        $this->_redirect($redirect);
    }
}
