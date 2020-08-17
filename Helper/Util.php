<?php
/*
 * @category    Sezzle
 * @package     Sezzle_Sezzlepay
 * @copyright   Copyright (c) Sezzle (https://www.sezzle.com/)
 */

namespace Sezzle\Sezzlepay\Helper;

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
     * Format to cents
     *
     * @param int $amount
     * @return int
     */
    public static function formatToCents($amount = 0)
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

        if ((strcmp($parts[0], 0) === 0) && (strcmp($parts[1], '00') === 0)) {
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
     * @param string $amount
     * @return string
     */
    protected static function formatMoney($amount)
    {
        return sprintf(self::MONEY_FORMAT, $amount);
    }
}
