<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Patupaa\PatupaaSettings;

final class TemplateProductService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function variants(): array
    {
        return (array) config('template_products.products', []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function variant(string $key): ?array
    {
        $variants = self::variants();

        return $variants[$key] ?? null;
    }

    public static function featureSummary(array $variant): string
    {
        $labels = [];

        foreach ((array) ($variant['features'] ?? []) as $feature => $enabled) {
            if ($enabled) {
                $labels[] = ucfirst(str_replace('_', ' ', (string) $feature));
            }
        }

        return implode(', ', $labels);
    }

    public static function serviceSummary(array $variant): string
    {
        $labels = [];

        foreach ((array) ($variant['services'] ?? []) as $service => $enabled) {
            if ($enabled) {
                $labels[] = ucfirst(str_replace('_', ' ', (string) $service));
            }
        }

        return $labels === [] ? 'No provider services' : implode(', ', $labels);
    }

    /**
     * @return array<string, bool>
     */
    public static function services(array $variant): array
    {
        return (array) ($variant['services'] ?? []);
    }

    /**
     * @return array<string, string>
     */
    public static function themeColours(array $variant): array
    {
        $presets = ThemeSettings::presets();
        $theme = (string) ($variant['theme'] ?? '');

        return $presets[$theme]['colors'] ?? ($presets[ThemeSettings::defaultKey()]['colors'] ?? []);
    }

    public static function serviceLabels(array $variant): array
    {
        $labels = [];

        foreach (self::services($variant) as $key => $enabled) {
            if ($enabled) {
                $labels[] = PatupaaSettings::TYPES[$key] ?? ucfirst(str_replace('_', ' ', (string) $key));
            }
        }

        return $labels;
    }

    public static function currency(): string
    {
        return (string) config('template_products.currency', 'GHS');
    }

    public static function docsUrl(): string
    {
        return (string) config('template_products.docs_url', '');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function tiers(): array
    {
        return (array) config('template_products.tiers', []);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function tiersFor(string $product): array
    {
        $variant = self::variant($product);

        if ($variant === null) {
            return [];
        }

        $prices = (array) ($variant['license_prices'] ?? []);
        $tiers = [];

        foreach (self::tiers() as $key => $tier) {
            $tiers[$key] = array_merge($tier, [
                'key' => $key,
                'price' => (float) ($prices[$key] ?? 0),
            ]);
        }

        return $tiers;
    }

    public static function licensePrice(string $product, string $tier): ?float
    {
        $tiers = self::tiersFor($product);

        return isset($tiers[$tier]) ? (float) $tiers[$tier]['price'] : null;
    }

    /**
     * @return array<int, string>
     */
    public static function includes(): array
    {
        return (array) config('template_products.includes', []);
    }
}