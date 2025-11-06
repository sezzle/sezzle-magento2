<?php

declare(strict_types=1);

/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Api\Data;


/**
 * Interface CustomerInterface
 * @package Sezzle\Sezzlepay\Api\Data
 */
interface CustomerInterface
{
    public const EMAIL = "email";
    public const FIRST_NAME = "first_name";
    public const LAST_NAME = "last_name";
    public const PHONE = "phone";
    public const DOB = "dob";
    public const BILLING_ADDRESS = "billing_address";
    public const SHIPPING_ADDRESS = "shipping_address";

    /**
     * @return string|null
     */
    public function getEmail(): ?string;

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail(string $email): self;

    /**
     * @return string|null
     */
    public function getFirstName(): ?string;

    /**
     * @param string $firstName
     * @return $this
     */
    public function setFirstName(string $firstName): self;

    /**
     * @return string|null
     */
    public function getLastName(): ?string;

    /**
     * @param string $lastName
     * @return $this
     */
    public function setLastName(string $lastName): self;

    /**
     * @return string|null
     */
    public function getPhone(): ?string;

    /**
     * @param string $phone
     * @return $this
     */
    public function setPhone(string $phone): self;

    /**
     * @return string|null
     */
    public function getDob(): ?string;

    /**
     * @param string $dob
     * @return $this
     */
    public function setDob(string $dob): self;

    /**
     * @return \Sezzle\Sezzlepay\Api\Data\AddressInterface|null
     */
    public function getBillingAddress(): ?AddressInterface;

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\AddressInterface|null $billingAddress
     * @return $this
     */
    public function setBillingAddress(?AddressInterface $billingAddress = null): self;

    /**
     * @return \Sezzle\Sezzlepay\Api\Data\AddressInterface|null
     */
    public function getShippingAddress(): ?AddressInterface;

    /**
     * @param \Sezzle\Sezzlepay\Api\Data\AddressInterface|null $shippingAddress
     * @return $this
     */
    public function setShippingAddress(?AddressInterface $shippingAddress = null): self;

}
