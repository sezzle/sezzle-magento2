<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Api;

/**
 * Interface ShippingInformationManagementInterface
 * @package Sezzle\Sezzlepay\Api
 */
interface ShippingInformationManagementInterface
{
    /**
     * Update Sezzle order with shipping and tax amounts based on address
     *
     * @param int $cartId
     * @param string $countryCode
     * @param string $state
     * @param string $city
     * @param string $postalCode
     * @param string $street
     * @param string $street2
     * @param string $addressUuid
     * @param string $firstName
     * @param string $lastName
     * @param string $phone
     * @return string JSON with success status
     */
    public function updateOrderWithAddress(
        int $cartId,
        string $countryCode,
        string $state,
        string $city,
        string $postalCode,
        string $street,
        string $street2,
        string $addressUuid,
        string $firstName,
        string $lastName,
        string $phone,
    ): string;
}
