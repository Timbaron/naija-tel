<?php

declare(strict_types=1);

namespace NaijaTel\Support;

class PhoneNumberFormatter
{
    /**
     * Format an 11-digit national number into spaced format: "0803 123 4567".
     */
    public static function national(string $digits): string
    {
        $clean = preg_replace('/\D/', '', $digits) ?? '';

        if (str_starts_with($clean, '234') && strlen($clean) === 13) {
            $clean = '0'.substr($clean, 3);
        } elseif (! str_starts_with($clean, '0') && strlen($clean) === 10) {
            $clean = '0'.$clean;
        }

        if (strlen($clean) === 11) {
            return substr($clean, 0, 4).' '.substr($clean, 4, 3).' '.substr($clean, 7, 4);
        }

        return $digits;
    }

    /**
     * Format into spaced international format: "+234 803 123 4567".
     */
    public static function international(string $digits): string
    {
        $clean = preg_replace('/\D/', '', $digits) ?? '';

        if (str_starts_with($clean, '234') && strlen($clean) === 13) {
            $clean = substr($clean, 3);
        } elseif (str_starts_with($clean, '0') && strlen($clean) === 11) {
            $clean = substr($clean, 1);
        }

        if (strlen($clean) === 10) {
            return '+234 '.substr($clean, 0, 3).' '.substr($clean, 3, 3).' '.substr($clean, 6, 4);
        }

        return $digits;
    }

    /**
     * Mask a phone number for logs, exceptions, and safe display: "+234 803 *** *567".
     */
    public static function mask(string $digits): string
    {
        $clean = preg_replace('/\D/', '', $digits) ?? '';

        if (str_starts_with($clean, '234') && strlen($clean) === 13) {
            $nsn = substr($clean, 3);

            return '+234 '.substr($nsn, 0, 3).' *** *'.substr($nsn, -3);
        }

        if (str_starts_with($clean, '0') && strlen($clean) === 11) {
            return substr($clean, 0, 4).' *** *'.substr($clean, -3);
        }

        if (strlen($clean) === 10) {
            return substr($clean, 0, 3).' *** *'.substr($clean, -3);
        }

        $len = strlen($digits);
        if ($len <= 4) {
            return '****';
        }

        return substr($digits, 0, 3).'****'.substr($digits, -2);
    }
}
