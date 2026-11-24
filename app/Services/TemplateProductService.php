<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Services\Patupaa\PatupaaSettings;
use Illuminate\Support\Facades\DB;

final class TemplateProductService
{
    private const ACTIVE_KEY = 'active_template_product';

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
    public static function active(): ?array
    {
        $raw = SettingsService::get(self::ACTIVE_KEY);
        $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Apply a product template (features, services, design) to this deployment.
     *
     * @return array<string, mixed>
     */
    public function apply(string $key, User $user): array
    {
        $variants = self::variants();

        if (! array_key_exists($key, $variants)) {
            abort(404);
        }

        $variant = $variants[$key];

        DB::transaction(function () use ($key, $variant, $user): void {
            FeatureSettings::set((array) $variant['features']);
            PatupaaSettings::setToggles((array) $variant['services']);
            PatupaaSettings::setAfaPrice((float) ($variant['afa_price'] ?? 0));
            ThemeSettings::set((string) $variant['theme']);

            SettingsService::setMany([
                self::ACTIVE_KEY => json_encode([
                    'key' => $key,
                    'name' => $variant['name'],
                    'applied_at' => now()->toIso8601String(),
                    'applied_by' => $user->email,
                ]),
            ]);
        });

        return $variant;
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
}
