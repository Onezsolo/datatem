<?php

declare(strict_types=1);

namespace App\Services\Patupaa;

use App\Services\SettingsService;

final class PatupaaSettings
{
    private const SETTINGS_KEY = 'patupaa_service_toggles';

    private const AFA_PRICE_KEY = 'patupaa_afa_price';

    public const TYPES = [
        'data_bundle' => 'Data bundles',
        'digital' => 'Subscriptions',
        'waec' => 'WAEC PINs',
        'utility' => 'Utility bills',
        'afa' => 'AFA registration',
    ];

    /**
     * @return array<string, bool>
     */
    public static function toggles(): array
    {
        $defaults = array_fill_keys(array_keys(self::TYPES), true);
        $raw = SettingsService::get(self::SETTINGS_KEY);
        $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

        if (! is_array($decoded)) {
            return $defaults;
        }

        foreach ($decoded as $key => $value) {
            if (array_key_exists($key, $defaults)) {
                $defaults[$key] = (bool) $value;
            }
        }

        return $defaults;
    }

    /**
     * @param  array<string, mixed>  $toggles
     * @return array<string, bool>
     */
    public static function setToggles(array $toggles): array
    {
        $current = self::toggles();

        foreach ($current as $key => $value) {
            if (array_key_exists($key, $toggles)) {
                $current[$key] = (bool) $toggles[$key];
            }
        }

        SettingsService::setMany([self::SETTINGS_KEY => json_encode($current)]);

        return $current;
    }

    public static function enabled(string $type): bool
    {
        return (bool) (self::toggles()[$type] ?? false);
    }

    public static function afaPrice(): float
    {
        return (float) (SettingsService::get(self::AFA_PRICE_KEY) ?? 0);
    }

    public static function setAfaPrice(float $price): void
    {
        SettingsService::setMany([self::AFA_PRICE_KEY => number_format(max(0, $price), 2, '.', '')]);
    }
}
