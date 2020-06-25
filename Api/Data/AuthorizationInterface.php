<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Api\Data;


/**
 * Interface AuthorizationInterface
 * @package Sezzle\Sezzlepay\Api\Data
 */
interface AuthorizationInterface
{
    const UUID = "uuid";
    const LINKS = "links";
    const AUTHORIZATION_AMOUNT = "authorization_amount";
    const APPROVED = "approved";
    const EXPIRATION = "expiration";
    const RELEASES = "releases";
    const CAPTURES = "captures";
    const REFUNDS = "refunds";

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
     * @return \Sezzle\Sezzlepay\Api\Data\LinkInterface[]|null
     */
    public function getLinks();

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\LinkInterface[] $links
     * @return $this
     */
    public function setLinks(array $links = null);

    /**
     * @return \Sezzle\Sezzlepay\Api\Data\AmountInterface|null
     */
    public function getAuthorizationAmount();

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\AmountInterface $authorizationAmount
     * @return $this
     */
    public function setAuthorizationAmount(AmountInterface $authorizationAmount = null);

    /**
     * @return string|null
     */
    public function getApproved();

    /**
     * @param string $approved
     * @return $this
     */
    public function setApproved($approved);

    /**
     * @return string|null
     */
    public function getExpiration();

    /**
     * @param string $expiration
     * @return $this
     */
    public function setExpiration($expiration);

    /**
     * @return \Sezzle\Sezzlepay\Api\Data\PaymentActionInterface[]|null
     */
    public function getReleases();

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\PaymentActionInterface[] $releases
     * @return $this
     */
    public function setReleases(array $releases = null);

    /**
     * @return \Sezzle\Sezzlepay\Api\Data\PaymentActionInterface[]|null
     */
    public function getCaptures();

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\PaymentActionInterface[] $captures
     * @return $this
     */
    public function setCaptures(array $captures = null);

    /**
     * @return \Sezzle\Sezzlepay\Api\Data\PaymentActionInterface[]|null
     */
    public function getRefunds();

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\PaymentActionInterface[] $refunds
     * @return $this
     */
    public function setRefunds(array $refunds = null);

}
