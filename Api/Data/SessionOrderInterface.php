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
    public function getUuid();

    /**
     * @param string $uuid
     * @return $this
     */
    public function setUuid($uuid);

    /**
     * @return string|null
     */
    public function getCheckoutUrl();

    /**
     * @param string $checkoutURL
     * @return $this
     */
    public function setCheckoutUrl($checkoutURL);
}
