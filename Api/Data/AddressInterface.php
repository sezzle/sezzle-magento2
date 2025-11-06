<?php

declare(strict_types=1);

/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Api\Data;


/**
 * Interface AddressInterface
 * @package Sezzle\Sezzlepay\Api\Data
 */
interface AddressInterface
{
    public const NAME = "name";
    public const CITY = "city";
    public const COUNTRY_CODE = "country_code";
    public const PHONE = "phone";
    public const POSTAL_CODE = "postal_code";
    public const STATE = "state";
    public const STREET = "street";
    public const STREET2 = "street2";

    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * @return string|null
     */
    public function getCity(): ?string;

    /**
     * @param string $city
     * @return $this
     */
    public function setCity(string $city): self;

    /**
     * @return string|null
     */
    public function getCountryCode(): ?string;

    /**
     * @param string $countryCode
     * @return $this
     */
    public function setCountryCode(string $countryCode): self;

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
    public function getPostalCode(): ?string;

    /**
     * @param string $postalCode
     * @return $this
     */
    public function setPostalCode(string $postalCode): self;

    /**
     * @return string|null
     */
    public function getState(): ?string;

    /**
     * @param string $state
     * @return $this
     */
    public function setState(string $state): self;

    /**
     * @return string|null
     */
    public function getStreet(): ?string;

    /**
     * @param string $street
     * @return $this
     */
    public function setStreet(string $street): self;

    /**
     * @return string|null
     */
    public function getStreet2(): ?string;

    /**
     * @param string $street2
     * @return $this
     */
    public function setStreet2(string $street2): self;
}
