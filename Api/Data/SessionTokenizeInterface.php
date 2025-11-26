<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Api\Data;


/**
 * Interface SessionTokenizeInterface
 * @package Sezzle\Sezzlepay\Api\Data
 */
interface SessionTokenizeInterface
{
    public const TOKEN = "token";
    public const STATUS = "status";
    public const APPROVAL_URL = "approval_url";
    public const EXPIRATION = "expiration";
    public const CUSTOMER = "customer";
    public const LINKS = "links";

    /**
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * @return string|null
     */
    public function getToken(): ?string;

    /**
     * @param string $token
     * @return $this
     */
    public function setToken(string $token): self;

    /**
     * @return string|null
     */
    public function getApprovalUrl(): ?string;

    /**
     * @param string $approvalURL
     * @return $this
     */
    public function setApprovalUrl(string $approvalURL): self;

    /**
     * @return string|null
     */
    public function getExpiration(): ?string;

    /**
     * @param string $expiration
     * @return $this
     */
    public function setExpiration(string $expiration): self;

    /**
     * @return \Sezzle\Sezzlepay\Api\Data\TokenizeCustomerInterface|null
     */
    public function getCustomer(): ?TokenizeCustomerInterface;

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\TokenizeCustomerInterface $customer
     * @return $this
     */
    public function setCustomer(?TokenizeCustomerInterface $customer = null): self;

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
