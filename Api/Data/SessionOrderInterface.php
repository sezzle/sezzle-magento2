<?php


namespace Sezzle\Sezzlepay\Api\Data;


/**
 * Interface SessionOrderInterface
 * @package Sezzle\Sezzlepay\Api\Data
 */
interface SessionOrderInterface
{
    const UUID = "uuid";
    const CHECKOUT_URL = "checkout_url";

    /**
     * @return string|null
     */
    public function getUUID();

    /**
     * @param string $uuid
     * @return $this
     */
    public function setUUID($uuid);

    /**
     * @return string|null
     */
    public function getCheckoutURL();

    /**
     * @param string $checkoutURL
     * @return $this
     */
    public function setCheckoutURL($checkoutURL);

}
