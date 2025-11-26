<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Sezzlepay\Api\Data\AddressInterface;
use Sezzle\Sezzlepay\Api\Data\CustomerInterface;

class Customer extends AbstractExtensibleObject implements CustomerInterface
{

    /**
     * @inheritDoc
     */
    public function getEmail(): ?string
    {
        return $this->_get(self::EMAIL);
    }

    /**
     * @inheritDoc
     */
    public function setEmail(string $email): self
    {
        $this->setData(self::EMAIL, $email);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getFirstName(): ?string
    {
        return $this->_get(self::FIRST_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setFirstName(string $firstName): self
    {
        $this->setData(self::FIRST_NAME, $firstName);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getLastName(): ?string
    {
        return $this->_get(self::LAST_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setLastName(string $lastName): self
    {
        $this->setData(self::LAST_NAME, $lastName);
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
    public function getDob(): ?string
    {
        return $this->_get(self::DOB);
    }

    /**
     * @inheritDoc
     */
    public function setDob(string $dob): self
    {
        $this->setData(self::DOB, $dob);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getBillingAddress(): ?AddressInterface
    {
        return $this->_get(self::BILLING_ADDRESS);
    }

    /**
     * @inheritDoc
     */
    public function setBillingAddress(?AddressInterface $billingAddress = null): self
    {
        $this->setData(self::BILLING_ADDRESS, $billingAddress);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getShippingAddress(): ?AddressInterface
    {
        return $this->_get(self::SHIPPING_ADDRESS);
    }

    /**
     * @inheritDoc
     */
    public function setShippingAddress(?AddressInterface $shippingAddress = null): self
    {
        $this->setData(self::SHIPPING_ADDRESS, $shippingAddress);
        return $this;
    }
}
