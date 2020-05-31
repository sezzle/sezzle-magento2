<?php


namespace Sezzle\Sezzlepay\Api\Data;


/**
 * Interface AuthorizationInterface
 * @package Sezzle\Sezzlepay\Api\Data
 */
interface AuthorizationInterface
{
    const UUID = "uuid";
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
     * @return AmountInterface|null
     */
    public function getAuthorizationAmount();

    /**
     * @param AmountInterface $authorizationAmount
     * @return $this
     */
    public function setAuthorizationAmount(AmountInterface $authorizationAmount);

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
