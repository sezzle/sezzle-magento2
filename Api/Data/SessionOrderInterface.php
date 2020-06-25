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
    const UUID = "uuid";
    const CHECKOUT_URL = "checkout_url";
    const LINKS = "links";

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

    /**
     * @return \Sezzle\Sezzlepay\Api\Data\LinkInterface[]|null
     */
    public function getLinks();

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\LinkInterface[] $links
     * @return $this
     */
    public function setLinks(array $links = null);
}
