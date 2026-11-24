<?php

declare(strict_types=1);

namespace App\Shop\Services;

use App\Services\SettingsService;

final class ShopSettings
{
    /**
     * @return array<string, array{label: string, fee: float}>
     */
    public static function deliveryZones(): array
    {
        $raw = SettingsService::get('shop_delivery_zones');

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);

            if (is_array($decoded) && $decoded !== []) {
                return $decoded;
            }
        }

        return (array) config('delivery.zones', []);
    }

    public static function defaultZone(): string
    {
        $zones = self::deliveryZones();
        $value = (string) SettingsService::get('shop_default_zone', '');

        return $value !== '' && array_key_exists($value, $zones)
            ? $value
            : (string) config('delivery.default_zone', array_key_first($zones) ?: 'accra');
    }

    public static function serviceCharge(): float
    {
        $value = SettingsService::get('shop_service_charge');

        return $value !== null && $value !== ''
            ? (float) $value
            : (float) config('delivery.service_charge', 0);
    }

    /**
     * @param  array<string, array{label: string, fee: float|int|string}>  $zones
     */
    public static function saveDelivery(array $zones, string $defaultZone, float $serviceCharge): void
    {
        $normalised = [];

        foreach ($zones as $key => $zone) {
            $key = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $key)) ?: 'zone';
            $normalised[$key] = [
                'label' => (string) ($zone['label'] ?? ucfirst($key)),
                'fee' => round((float) ($zone['fee'] ?? 0), 2),
            ];
        }

        SettingsService::setMany([
            'shop_delivery_zones' => json_encode($normalised),
            'shop_default_zone' => array_key_exists($defaultZone, $normalised) ? $defaultZone : (string) array_key_first($normalised),
            'shop_service_charge' => (string) round($serviceCharge, 2),
        ]);
    }
}
