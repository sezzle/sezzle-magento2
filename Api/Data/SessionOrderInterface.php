<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Api\Data;

/**
 * Interface SessionOrderInterface
 * @package Sezzle\Sezzlepay\Api\Data
 */
interface SessionOrderInterface
{
    public const UUID = "uuid";
    public const CHECKOUT_URL = "checkout_url";
    public const LINKS = "links";

    /**
     * @return string|null
     */
    public function getUuid(): ?string;

    /**
     * @param string $uuid
     * @return $this
     */
    public function setUuid(string $uuid): self;

    /**
     * @return string|null
     */
    public function getCheckoutUrl(): ?string;

    /**
     * @param string $checkoutURL
     * @return $this
     */
    public function setCheckoutUrl(string $checkoutURL): self;

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
