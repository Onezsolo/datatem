<?php

namespace App\Support;

/**
 * Normalize Ghana phone numbers for storage and lookup.
 * Accepts: 0551234567, 233551234567, +233 55 123 4567.
 * Returns canonical form: 0XXXXXXXXX (10 digits) or null if invalid.
 */
final class GhanaPhone
{
    /** Ghana mobile prefix after 0: 24, 25, 26 (Vodafone), 20, 50 (MTN), 54, 55, 59 (AirtelTigo), etc. */
    private const LOCAL_PREFIX_REGEX = '/^0[235]\d{8}$/';

    public static function normalize(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', $value);
        if (strlen($digits) === 12 && str_starts_with($digits, '233')) {
            $digits = '0' . substr($digits, 3);
        }
        if (strlen($digits) === 10 && preg_match(self::LOCAL_PREFIX_REGEX, $digits)) {
            return $digits;
        }
        return null;
    }

    /** Check if the value is a valid Ghana mobile number (after normalization). */
    public static function isValid(?string $value): bool
    {
        return self::normalize($value) !== null;
    }
}
