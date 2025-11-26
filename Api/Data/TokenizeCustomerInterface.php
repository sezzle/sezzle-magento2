<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Api\Data;


/**
 * Interface TokenizeCustomerInterface
 * @package Sezzle\Sezzlepay\Api\Data
 */
interface TokenizeCustomerInterface
{
    public const UUID = "uuid";
    public const EXPIRATION = "expiration";
    public const LINKS = "links";

    /**
     * @return string
     */
    public function getUuid(): string;

    /**
     * @param string $uuid
     * @return $this
     */
    public function setUuid(string $uuid): self;

    /**
     * @return string
     */
    public function getExpiration(): string;

    /**
     * @param string $expiration
     * @return $this
     */
    public function setExpiration(string $expiration): self;

    /**
     * @return \Sezzle\Sezzlepay\Api\Data\LinkInterface[]|null
     */
    public function getLinks(): ?array;

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\LinkInterface[] $links
     * @return $this
     */
    public function setLinks(?array $links = null): self;

}
