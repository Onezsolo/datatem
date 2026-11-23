<?php

namespace App\Services;

/**
 * Generate primary color shades (50-900) from a base hex and provide gradient colors for the site theme.
 * Network colors (MTN, AirtelTigo, Telecel) are not managed here.
 */
final class ThemeColorService
{
    /** Default purple base (Tailwind violet-600). */
    private const DEFAULT_PRIMARY = '#9333ea';

    /** Default gradient start (lighter purple). */
    private const DEFAULT_GRADIENT_START = '#a855f7';

    /** Default gradient end (darker purple). */
    private const DEFAULT_GRADIENT_END = '#6b21a8';

    /**
     * Primary color shades for Tailwind (50, 100, 200, 300, 400, 500, 600, 700, 800, 900).
     * Generated from theme_primary_hex; 500/600 use the base, lighter shades mix with white, darker mix with black.
     *
     * @return array<int, string> map of shade number => hex
     */
    public static function getPrimaryShades(): array
    {
        $base = SettingsService::get('theme_primary_hex', self::DEFAULT_PRIMARY);
        $base = self::normalizeHex($base) ?: self::DEFAULT_PRIMARY;
        $rgb = self::hexToRgb($base);
        if ($rgb === null) {
            return self::defaultShades();
        }

        return [
            50 => self::mixRgb($rgb, [255, 255, 255], 0.97),
            100 => self::mixRgb($rgb, [255, 255, 255], 0.90),
            200 => self::mixRgb($rgb, [255, 255, 255], 0.80),
            300 => self::mixRgb($rgb, [255, 255, 255], 0.60),
            400 => self::mixRgb($rgb, [255, 255, 255], 0.30),
            500 => self::mixRgb($rgb, [255, 255, 255], 0.10),
            600 => $base,
            700 => self::mixRgb($rgb, [0, 0, 0], 0.15),
            800 => self::mixRgb($rgb, [0, 0, 0], 0.30),
            900 => self::mixRgb($rgb, [0, 0, 0], 0.40),
        ];
    }

    /** Gradient start hex (for .gradient-text and accents). */
    public static function getGradientStart(): string
    {
        $v = SettingsService::get('theme_gradient_start', self::DEFAULT_GRADIENT_START);
        return self::normalizeHex($v) ?: self::DEFAULT_GRADIENT_START;
    }

    /** Gradient end hex. */
    public static function getGradientEnd(): string
    {
        $v = SettingsService::get('theme_gradient_end', self::DEFAULT_GRADIENT_END);
        return self::normalizeHex($v) ?: self::DEFAULT_GRADIENT_END;
    }

    /** Default primary shades (Tailwind violet). */
    public static function defaultShades(): array
    {
        return [
            50 => '#faf5ff',
            100 => '#f3e8ff',
            200 => '#e9d5ff',
            300 => '#d8b4fe',
            400 => '#c084fc',
            500 => '#a855f7',
            600 => '#9333ea',
            700 => '#7e22ce',
            800 => '#6b21a8',
            900 => '#581c87',
        ];
    }

    private static function normalizeHex(string $hex): ?string
    {
        $hex = preg_replace('/^#/', '', trim($hex));
        if (preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            return '#' . $hex;
        }
        if (preg_match('/^[0-9A-Fa-f]{3}$/', $hex)) {
            return '#' . $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return null;
    }

    /** @return int[]|null [r,g,b] */
    private static function hexToRgb(string $hex): ?array
    {
        $hex = preg_replace('/^#/', '', $hex);
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return null;
        }
        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Mix base rgb with target rgb. Factor 0 = all base, 1 = all target.
     *
     * @param int[] $base
     * @param int[] $target
     */
    private static function mixRgb(array $base, array $target, float $targetFactor): string
    {
        $r = (int) round($base[0] * (1 - $targetFactor) + $target[0] * $targetFactor);
        $g = (int) round($base[1] * (1 - $targetFactor) + $target[1] * $targetFactor);
        $b = (int) round($base[2] * (1 - $targetFactor) + $target[2] * $targetFactor);
        $r = max(0, min(255, $r));
        $g = max(0, min(255, $g));
        $b = max(0, min(255, $b));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
