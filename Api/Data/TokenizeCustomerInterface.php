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
    const LINKS = "links";

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

    /**
     * @return \Sezzle\Payment\Api\Data\LinkInterface[]|null
     */
    public function getLinks();

    /**
     * @param \Sezzle\Payment\Api\Data\LinkInterface[] $links
     * @return $this
     */
    public function setLinks(array $links = null);

}
