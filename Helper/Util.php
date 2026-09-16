<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Helper;

use Magento\Quote\Model\Quote\Address;

/**
 * Class Action
 */
class Util
{
    /**
     * Money format
     */
    const MONEY_FORMAT = "%.2f";

    /**
     * Address fields holding shopper entered data
     *
     * Country is left out on purpose. Magento pre-selects the store default country
     * on every address form, so an untouched address always carries one.
     */
    const ADDRESS_DATA_FIELDS = [
        "firstname",
        "lastname",
        "company",
        "street",
        "city",
        "region",
        "region_id",
        "postcode",
        "telephone",
        "vat_id"
    ];

    /**
     * Check whether an address holds no shopper entered data
     *
     * Typed rather than guarded by instanceof. True here means skip or omit, so a
     * caller that hands over the wrong thing should get a TypeError at its own call
     * site rather than a quiet "empty" that drops billing from the Sezzle payload
     * three layers away. Null stays true: a missing address holds no data.
     *
     * @param Address|null $address
     * @return bool
     */
    public static function isAddressEmpty(?Address $address = null): bool
    {
        if ($address === null) {
            return true;
        }

        foreach (self::ADDRESS_DATA_FIELDS as $field) {
            $value = $address->getData($field);
            if (is_array($value)) {
                $value = implode('', $value);
            }

            if (trim((string)$value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Format to cents
     *
     * @param float $amount
     * @return int
     */
    public static function formatToCents($amount = 0.00)
    {
        $negative = false;
        $str = self::formatMoney($amount);
        if (strcmp($str[0], '-') === 0) {
            // treat it like a positive. then prepend a '-' to the return value.
            $str = substr($str, 1);
            $negative = true;
        }

        $parts = explode('.', $str, 2);
        if (($parts === false) || empty($parts)) {
            return 0;
        }

        if ((strcmp($parts[0], '0') === 0) && (strcmp($parts[1], '00') === 0)) {
            return 0;
        }

        $retVal = '';
        if ($negative) {
            $retVal .= '-';
        }
        $retVal .= ltrim($parts[0] . substr($parts[1], 0, 2), '0');
        return intval($retVal);
    }

    /**
     * Format money
     *
     * @param float $amount
     * @return string
     */
    protected static function formatMoney($amount)
    {
        return sprintf(self::MONEY_FORMAT, $amount);
    }
}
