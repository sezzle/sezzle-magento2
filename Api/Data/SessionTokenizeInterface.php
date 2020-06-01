<?php


namespace Sezzle\Payment\Api\Data;


/**
 * Interface SessionTokenizeInterface
 * @package Sezzle\Payment\Api\Data
 */
interface SessionTokenizeInterface
{
    const TOKEN = "token";
    const STATUS = "status";
    const APPROVAL_URL = "approval_url";
    const EXPIRATION = "expiration";
    const CUSTOMER = "customer";

    /**
     * @return string|null
     */
    public function getStatus();

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return string|null
     */
    public function getToken();

    /**
     * @param string $token
     * @return $this
     */
    public function setToken($token);

    /**
     * @return string|null
     */
    public function getApprovalUrl();

    /**
     * @param string $approvalURL
     * @return $this
     */
    public function setApprovalUrl($approvalURL);

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
     * @return \Sezzle\Payment\Api\Data\TokenizeCustomerInterface|null
     */
    public function getCustomer();

    /**
     * @param \Sezzle\Payment\Api\Data\TokenizeCustomerInterface $customer
     * @return $this
     */
    public function setCustomer(TokenizeCustomerInterface $customer = null);

}
