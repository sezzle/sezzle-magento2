<?php


namespace Sezzle\Payment\Api\Data;


/**
 * Interface TokenizeCustomerInterface
 * @package Sezzle\Payment\Api\Data
 */
interface TokenizeCustomerInterface
{
    const UUID = "uuid";
    const EXPIRATION = "expiration";

    /**
     * @return string
     */
    public function getUuid();

    /**
     * @param string $uuid
     * @return $this
     */
    public function setUuid($uuid);

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
