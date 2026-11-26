<?php

namespace App\Support;

class IndonesianPhone
{
    /**
     * Store an Indonesian mobile number as 628 followed by the subscriber number.
     *
     * Spaces and dashes are removed. A leading +62 keeps the digits, a leading 0
     * is replaced with 62, and any other number is prefixed with 62. A number that
     * already starts with 62 is left unchanged.
     */
    public static function normalize(string $phone): string
    {
        $normalized = preg_replace('/[\s-]+/', '', $phone) ?? '';

        if (str_starts_with($normalized, '+62')) {
            return substr($normalized, 1);
        }

        if (str_starts_with($normalized, '62')) {
            return $normalized;
        }

        if (str_starts_with($normalized, '0')) {
            return '62'.substr($normalized, 1);
        }

        return '62'.$normalized;
    }
}
