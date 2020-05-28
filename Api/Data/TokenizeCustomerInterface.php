<?php


namespace Sezzle\Sezzlepay\Api\Data;


/**
 * Interface TokenizeCustomerInterface
 * @package Sezzle\Sezzlepay\Api\Data
 */
interface TokenizeCustomerInterface
{
    const UUID = "uuid";
    const EXPIRATION = "expiration";

    /**
     * @return string
     */
    public function getUUID();

    /**
     * @param string $uuid
     * @return $this
     */
    public function setUUID($uuid);

    /**
     * @return string
     */
    public function getExpiration();

    /**
     * @param string $expiration
     * @return $this
     */
    public function setExpiration($expiration);

}
