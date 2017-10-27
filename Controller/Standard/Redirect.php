<?php
namespace Sezzle\Sezzlepay\Controller\Standard;
class Redirect extends \Sezzle\Sezzlepay\Controller\Sezzlepay
{
    public function execute()
    {
        $order = $this->getOrder();
        $this->getResponse()->setRedirect(
            $this->getSezzlepayModel()->buildSezzlepayRequest($order)
        );
    }
}