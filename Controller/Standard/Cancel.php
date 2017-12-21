<?php
namespace Sezzle\Sezzlepay\Controller\Standard;

class Cancel extends \Sezzle\Sezzlepay\Controller\Sezzlepay
{
    public function execute()
    {
        $order = $this->getOrder();
        $this->cancelOrder($order, "Returned from Sezzlepay without completing payment.");
        $this->_checkoutSession->restoreQuote();
        $this->getResponse()->setRedirect(
            $this->_url->getUrl('checkout')
        );
    }
}
