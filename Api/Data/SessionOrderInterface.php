<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Api\Data;

/**
 * Interface SessionOrderInterface
 * @package Sezzle\Payment\Api\Data
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
     * @return \Sezzle\Payment\Api\Data\LinkInterface[]|null
     */
    public function getLinks();

    /**
     * @param \Sezzle\Payment\Api\Data\LinkInterface[] $links
     * @return $this
     */
    public function setLinks(array $links = null);
}
