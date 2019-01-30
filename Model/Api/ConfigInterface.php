<?php

namespace Sezzle\Sezzlepay\Model\Api;


interface ConfigInterface
{
    public function getAuthToken();

    public function getCompleteUrl($orderId, $reference);

    public function getCancelUrl();
}