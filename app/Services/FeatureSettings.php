<?php

declare(strict_types=1);

namespace App\Services;

final class FeatureSettings
{
    private const SETTINGS_KEY = 'feature_flags';

    /**
     * @return array<string, bool>
     */
    public static function all(): array
    {
        $definitions = (array) config('features.modules', []);
        $flags = array_fill_keys(array_keys($definitions), true);
        $raw = SettingsService::get(self::SETTINGS_KEY);
        $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

        if (is_array($decoded)) {
            foreach ($decoded as $key => $value) {
                if (array_key_exists($key, $flags)) {
                    $flags[$key] = (bool) $value;
                }
            }
        }

        return $flags;
    }

    public static function enabled(string $key): bool
    {
        return (bool) (self::all()[$key] ?? true);
    }

    /**
     * @param  array<string, mixed>  $flags
     * @return array<string, bool>
     */
    public static function set(array $flags): array
    {
        $current = self::all();

        foreach ($current as $key => $value) {
            if (array_key_exists($key, $flags)) {
                $current[$key] = (bool) $flags[$key];
            }
        }

        SettingsService::setMany([self::SETTINGS_KEY => json_encode($current)]);

        return $current;
    }

    /**
     * @return array<string, string>
     */
    public static function definitions(): array
    {
        return (array) config('features.modules', []);
    }
}
