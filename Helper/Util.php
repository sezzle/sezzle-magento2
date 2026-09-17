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
     * Placeholder written in place of a redacted value
     */
    const REDACTED = "***REDACTED***";

    /**
     * Keys whose values must never reach the log file
     *
     * The private key and the bearer token are the merchant's credentials for the Sezzle
     * API: anyone holding them can transact as the merchant. Public key is deliberately
     * absent - it is already handed to the browser by the checkout config provider, so it
     * is not a secret, and keeping it readable is what lets support tell which merchant
     * account a log line belongs to.
     */
    const SENSITIVE_LOG_KEYS = [
        "private_key",
        "token",
        "access_token",
        "refresh_token",
        "authorization",
        "password",
        "secret",
        "api_key",
        "apikey"
    ];

    /**
     * How far to walk into a nested log payload before giving up
     *
     * Log payloads are shallow in practice. The cap only exists so a pathological
     * structure cannot exhaust the stack on the order-failure path, where logging runs
     * ahead of the authorization release and must not throw.
     */
    const MAX_REDACTION_DEPTH = 10;

    /**
     * Strip merchant credentials out of a log payload
     *
     * Merchants routinely send var/log/sezzlepay.log to support when checkout breaks, so
     * anything written here should be assumed to travel by email. Redaction lives at the
     * log sink rather than at each call site because there are dozens of call sites and
     * one that forgets is a leaked credential.
     *
     * @param array $data
     * @param int $depth
     * @return array
     */
    public static function redactSensitive(array $data, int $depth = 0): array
    {
        if ($depth >= self::MAX_REDACTION_DEPTH) {
            return [self::REDACTED];
        }

        $redacted = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_LOG_KEYS, true)) {
                $redacted[$key] = self::REDACTED;
                continue;
            }

            if (is_array($value)) {
                $redacted[$key] = self::redactSensitive($value, $depth + 1);
                continue;
            }

            $redacted[$key] = is_string($value) ? self::redactSensitiveText($value) : $value;
        }

        return $redacted;
    }

    /**
     * Strip credentials out of an already serialized payload
     *
     * Some values reach the log as raw JSON rather than as an array - the gateway client
     * logs the response body exactly as curl returned it - so key matching alone would
     * walk straight past them.
     *
     * @param string $text
     * @return string
     */
    public static function redactSensitiveText(string $text): string
    {
        $keys = implode('|', array_map('preg_quote', self::SENSITIVE_LOG_KEYS));

        return (string)preg_replace(
            '/("(?:' . $keys . ')"\s*:\s*)"(?:[^"\\\\]|\\\\.)*"/i',
            '$1"' . self::REDACTED . '"',
            $text
        );
    }

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
