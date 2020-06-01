<?php


namespace Sezzle\Payment\Api\Data;


/**
 * Interface AddressInterface
 * @package Sezzle\Payment\Api\Data
 */
interface AddressInterface
{
    const NAME = "name";
    const CITY = "city";
    const COUNTRY_CODE = "country_code";
    const PHONE = "phone";
    const POSTAL_CODE = "postal_code";
    const STATE = "state";
    const STREET = "street";
    const STREET2 = "street2";

    /**
     * @return string|null
     */
    public function getName();

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * @return string|null
     */
    public function getCity();

    /**
     * @param string $city
     * @return $this
     */
    public function setCity($city);

    /**
     * @return string|null
     */
    public function getCountryCode();

    /**
     * @param string $countryCode
     * @return $this
     */
    public function setCountryCode($countryCode);

    /**
     * @return string|null
     */
    public function getPhone();

    /**
     * @param string $phone
     * @return $this
     */
    public function setPhone($phone);

    /**
     * @return string|null
     */
    public function getPostalCode();

    /**
     * @param string $postalCode
     * @return $this
     */
    public function setPostalCode($postalCode);

    /**
     * @return string|null
     */
    public function getState();

    /**
     * @param string $state
     * @return $this
     */
    public function setState($state);

    /**
     * @return string|null
     */
    public function getStreet();

    /**
     * @param string $street
     * @return $this
     */
    public function setStreet($street);

    /**
     * @return string|null
     */
    public function getStreet2();

    /**
     * @param string $street2
     * @return $this
     */
    public function setStreet2($street2);
}
