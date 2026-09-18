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
     * Written when redaction itself could not complete
     *
     * Distinct from REDACTED so a support engineer reading the log can tell "a credential was
     * removed here" apart from "this line could not be processed, so all of it was dropped".
     */
    const REDACTION_FAILED = "***REDACTED*** (redaction failed)";

    /**
     * Key endings whose values must never reach the log file
     *
     * Each entry is the word sequence a credential key ends with. Matched as a suffix rather
     * than as an exact name: the private key and the bearer token are the merchant's
     * credentials for the Sezzle API - anyone holding them can transact as the merchant - and
     * an exact-match list puts the burden back on every call site to spell the field exactly
     * as this list does. Suffix matching covers privateKey, merchant_token and the x-api-key
     * header without that dependency.
     *
     * Suffix rather than substring on purpose: "token" as a substring would also swallow
     * sezzle_tokenize_status, which is a diagnostic value support needs to be able to read.
     *
     * Held as words so that both matchers below can be derived from this one list - the array
     * walk joins them ("apikey", compared against a key stripped of punctuation) and the text
     * matcher joins them with an optional separator ("api[_-]?key", compared against the key
     * as it appears in the JSON).
     */
    const SENSITIVE_KEY_SUFFIXES = [
        ["private", "key"],
        ["token"],
        ["access", "token"],
        ["refresh", "token"],
        ["authorization"],
        ["password"],
        ["secret"],
        ["api", "key"]
    ];

    /**
     * Keys that stay readable however they are spelled
     *
     * Public key is already handed to the browser by the checkout config provider, so it is
     * not a secret, and keeping it readable is what lets support tell which merchant account
     * a log line belongs to. Listed explicitly rather than left to depend on it not colliding
     * with a suffix above, so that adding a suffix later cannot silently blind diagnostics.
     */
    const NEVER_REDACT_KEYS = [
        "publickey"
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
     * Compiled key/value pattern, built once
     *
     * The suffix list is a constant, but redactSensitiveText() runs for every string leaf of
     * every payload, and the gateway client logs a request and a response body on each Sezzle
     * API call. Caching keeps the hot path down to the match itself.
     *
     * @var string|null
     */
    private static $sensitivePattern;

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
            // Keyed rather than a bare list so the serialized payload still reads as an object
            // and the reader can see that something was dropped here, rather than that the
            // structure changed shape underneath them.
            return ['*' => self::REDACTED];
        }

        $redacted = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && self::isSensitiveKey($key)) {
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
     * Whether this key names a credential
     *
     * Normalised first, so private_key, privateKey, private-key and X-Api-Key all reduce to
     * the same thing. The allowlist is checked before the suffixes so an explicitly readable
     * key cannot be caught by a later addition to the list.
     *
     * @param string $key
     * @return bool
     */
    private static function isSensitiveKey(string $key): bool
    {
        $normalised = strtolower((string)preg_replace('/[^A-Za-z0-9]/', '', $key));
        if ($normalised === '' || in_array($normalised, self::NEVER_REDACT_KEYS, true)) {
            return false;
        }

        foreach (self::SENSITIVE_KEY_SUFFIXES as $words) {
            if (str_ends_with($normalised, implode('', $words))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Strip credentials out of an already serialized payload
     *
     * Some values reach the log as raw JSON rather than as an array - the gateway client
     * logs the response body exactly as curl returned it - so key matching alone would
     * walk straight past them.
     *
     * Covers three shapes the array walk cannot see: a key carrying a prefix ("X-Api-Key"),
     * a key whose quotes arrive escaped because the JSON is itself embedded in a JSON string
     * (\"token\":\"...\"), and a non-string value such as "token":12345 or "token":null.
     *
     * @param string $text
     * @return string
     */
    public static function redactSensitiveText(string $text): string
    {
        $result = preg_replace_callback(
            self::sensitivePattern(),
            static function (array $match): string {
                // Mirror the escaping of the value we are replacing, so an embedded JSON
                // document stays parseable after redaction instead of gaining a stray quote.
                $quote = str_starts_with($match[2], '\\"') ? '\\"' : '"';

                return $match[1] . $quote . self::REDACTED . $quote;
            },
            $text
        );

        // preg_replace_callback() returns null when the match runs past pcre.backtrack_limit
        // or exhausts the JIT stack, which a large response body can do. Dropping the line is
        // the safe direction - an unredacted credential is worse than a missing log entry -
        // but say so, rather than writing a blank line the reader cannot account for.
        return $result ?? self::REDACTION_FAILED;
    }

    /**
     * The key/value pattern applied to serialized payloads
     *
     * The sensitive key is required in the pattern itself rather than tested in the callback:
     * a pattern that matched every key would consume an ordinary value whole, and a response
     * body logged as a JSON string inside a JSON field carries its credentials inside exactly
     * such a value. Leaving non-sensitive pairs unmatched keeps the scan walking into them.
     *
     * Group 1 is the key and colon, replayed untouched; group 2 is the value, whose opening
     * quote tells the callback whether this is plain or escape-embedded JSON.
     *
     * Note the invariant that keeps public_key readable here as well as in the array walk: no
     * entry in SENSITIVE_KEY_SUFFIXES matches the end of "public_key". A future addition that
     * broke that would need the NEVER_REDACT_KEYS allowlist reflected into this pattern too.
     *
     * @return string
     */
    private static function sensitivePattern(): string
    {
        if (self::$sensitivePattern === null) {
            $suffixes = [];
            foreach (self::SENSITIVE_KEY_SUFFIXES as $words) {
                $suffixes[] = implode('[_\-]?', array_map('preg_quote', $words));
            }

            self::$sensitivePattern = '/(\\\\?"[A-Za-z0-9_\-]*(?:' . implode('|', $suffixes) . ')\\\\?"\s*:\s*)'
                . '("(?:[^"\\\\]|\\\\.)*"|\\\\"(?:(?!\\\\").)*\\\\"|-?\d+(?:\.\d+)?|true|false|null)/i';
        }

        return self::$sensitivePattern;
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
