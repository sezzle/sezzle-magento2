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
    public function getName()
    {
        return $this->_get(self::NAME);
    }

    /**
     * @inheritDoc
     */
    public function setName($name)
    {
        $this->setData(self::NAME, $name);
    }

    /**
     * @inheritDoc
     */
    public function getCity()
    {
        return $this->_get(self::CITY);
    }

    /**
     * @inheritDoc
     */
    public function setCity($city)
    {
        $this->setData(self::CITY, $city);
    }

    /**
     * @inheritDoc
     */
    public function getCountryCode()
    {
        return $this->_get(self::COUNTRY_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setCountryCode($countryCode)
    {
        $this->setData(self::COUNTRY_CODE, $countryCode);
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
    public function getPostalCode()
    {
        return $this->_get(self::POSTAL_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setPostalCode($postalCode)
    {
        $this->setData(self::POSTAL_CODE, $postalCode);
    }

    /**
     * @inheritDoc
     */
    public function getState()
    {
        return $this->_get(self::STATE);
    }

    /**
     * @inheritDoc
     */
    public function setState($state)
    {
        $this->setData(self::STATE, $state);
    }

    /**
     * @inheritDoc
     */
    public function getStreet()
    {
        return $this->_get(self::STREET);
    }

    /**
     * @inheritDoc
     */
    public function setStreet($street)
    {
        $this->setData(self::STREET, $street);
    }

    /**
     * @inheritDoc
     */
    public function getStreet2()
    {
        return $this->_get(self::STREET2);
    }

    /**
     * @inheritDoc
     */
    public function setStreet2($street2)
    {
        $this->setData(self::STREET2, $street2);
    }
}
