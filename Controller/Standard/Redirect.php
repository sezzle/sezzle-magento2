<?php
namespace Sezzle\Sezzlepay\Controller\Standard;
class Redirect extends \Sezzle\Sezzlepay\Controller\Sezzlepay
{
    public function execute()
    {
        $order = $this->getOrder();
        $requestBody = $this->getSezzlepayModel()->buildSezzlepayRequest($order);

        die(
            json_encode(
                $requestBody
            )
        );
    }
}