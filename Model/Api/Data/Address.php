<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Sezzlepay\Api\Data\AddressInterface;

class Address extends AbstractExtensibleObject implements AddressInterface
{

    /**
     * @inheritDoc
     */
    public function getName(): ?string
    {
        return $this->_get(self::NAME);
    }

    /**
     * @inheritDoc
     */
    public function setName(string $name): self
    {
        $this->setData(self::NAME, $name);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCity(): ?string
    {
        return $this->_get(self::CITY);
    }

    /**
     * @inheritDoc
     */
    public function setCity(string $city): self
    {
        $this->setData(self::CITY, $city);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCountryCode(): ?string
    {
        return $this->_get(self::COUNTRY_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setCountryCode(string $countryCode): self
    {
        $this->setData(self::COUNTRY_CODE, $countryCode);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPhone(): ?string
    {
        return $this->_get(self::PHONE);
    }

    /**
     * @inheritDoc
     */
    public function setPhone(string $phone): self
    {
        $this->setData(self::PHONE, $phone);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPostalCode(): ?string
    {
        return $this->_get(self::POSTAL_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setPostalCode(string $postalCode): self
    {
        $this->setData(self::POSTAL_CODE, $postalCode);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getState(): ?string
    {
        return $this->_get(self::STATE);
    }

    /**
     * @inheritDoc
     */
    public function setState(string $state): self
    {
        $this->setData(self::STATE, $state);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getStreet(): ?string
    {
        return $this->_get(self::STREET);
    }

    /**
     * @inheritDoc
     */
    public function setStreet(string $street): self
    {
        $this->setData(self::STREET, $street);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getStreet2(): ?string
    {
        return $this->_get(self::STREET2);
    }

    /**
     * @inheritDoc
     */
    public function setStreet2(string $street2): self
    {
        $this->setData(self::STREET2, $street2);
        return $this;
    }
}
