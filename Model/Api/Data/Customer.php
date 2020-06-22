<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Payment
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Payment\Model\Api\Data;


use Magento\Framework\Api\AbstractExtensibleObject;
use Sezzle\Payment\Api\Data\AddressInterface;
use Sezzle\Payment\Api\Data\CustomerInterface;

class Customer extends AbstractExtensibleObject implements CustomerInterface
{

    /**
     * @inheritDoc
     */
    public function getEmail()
    {
        return $this->_get(self::EMAIL);
    }

    /**
     * @inheritDoc
     */
    public function setEmail($email)
    {
        $this->setData(self::EMAIL, $email);
    }

    /**
     * @inheritDoc
     */
    public function getFirstName()
    {
        return $this->_get(self::FIRST_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setFirstName($firstName)
    {
        $this->setData(self::FIRST_NAME, $firstName);
    }

    /**
     * @inheritDoc
     */
    public function getLastName()
    {
        return $this->_get(self::LAST_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setLastName($lastName)
    {
        $this->setData(self::LAST_NAME, $lastName);
    }

    /**
     * @inheritDoc
     */
    public function getPhone()
    {
        return $this->_get(self::PHONE);
    }

    /**
     * @inheritDoc
     */
    public function setPhone($phone)
    {
        $this->setData(self::PHONE, $phone);
    }

    /**
     * @inheritDoc
     */
    public function getDob()
    {
        return $this->_get(self::DOB);
    }

    /**
     * @inheritDoc
     */
    public function setDob($dob)
    {
        $this->setData(self::DOB, $dob);
    }

    /**
     * @inheritDoc
     */
    public function getBillingAddress()
    {
        return $this->_get(self::BILLING_ADDRESS);
    }

    /**
     * @inheritDoc
     */
    public function setBillingAddress(AddressInterface $billingAddress = null)
    {
        $this->setData(self::BILLING_ADDRESS, $billingAddress);
    }

    /**
     * @inheritDoc
     */
    public function getShippingAddress()
    {
        return $this->_get(self::SHIPPING_ADDRESS);
    }

    /**
     * @inheritDoc
     */
    public function setShippingAddress(AddressInterface $shippingAddress = null)
    {
        $this->setData(self::SHIPPING_ADDRESS, $shippingAddress);
    }
}
