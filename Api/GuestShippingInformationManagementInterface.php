<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Api;

/**
 * Interface GuestShippingInformationManagementInterface
 * @package Sezzle\Sezzlepay\Api
 */
interface GuestShippingInformationManagementInterface
{
    /**
     * Update Sezzle order with shipping and tax amounts based on address for guest
     *
     * @param string $cartId
     * @param string $countryCode
     * @param string $state
     * @param string $city
     * @param string $postalCode
     * @param string $street
     * @param string $street2
     * @param string $addressUuid
     * @return string JSON with success status
     */
    public function updateOrderWithAddress(
        string $cartId,
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
