<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ServiceProvider;
use App\Models\TemplatePackage;
use App\Services\Patupaa\PatupaaSettings;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class TemplatePackageService
{
    private const APPLIED_KEY = 'applied_template_package';

    /**
     * @param  array<string, mixed>  $data
     */
    public function capture(array $data, User $user): TemplatePackage
    {
        $providers = ServiceProvider::query()->orderBy('sort_order')->get()->map(fn (ServiceProvider $provider): array => [
            'name' => $provider->name,
            'driver' => $provider->driver,
            'base_url' => $provider->base_url,
            'api_key' => (string) $provider->api_key,
            'api_secret' => (string) $provider->api_secret,
            'services' => $provider->services,
            'is_active' => $provider->is_active,
            'is_default' => $provider->is_default,
            'sort_order' => $provider->sort_order,
        ])->all();

        return TemplatePackage::create([
            'name' => (string) $data['name'],
            'version' => (string) ($data['version'] ?? '1.0.0'),
            'description' => $data['description'] ?? null,
            'payload' => [
                'theme' => ThemeSettings::currentKey(),
                'features' => FeatureSettings::all(),
                'services' => PatupaaSettings::toggles(),
                'afa_price' => PatupaaSettings::afaPrice(),
                'providers' => $providers,
            ],
            'created_by' => $user->id,
        ]);
    }

    /**
     * Apply a package to this deployment: template design, features, service switches,
     * AFA fee and service provider sources. Business data is untouched.
     *
     * @return array<string, mixed>
     */
    public function apply(TemplatePackage $package, User $user): array
    {
        $payload = $package->payload ?? [];

        return DB::transaction(function () use ($package, $payload, $user): array {
            if (isset($payload['theme'])) {
                ThemeSettings::set((string) $payload['theme']);
            }

            if (is_array($payload['features'] ?? null)) {
                FeatureSettings::set($payload['features']);
            }

            if (is_array($payload['services'] ?? null)) {
                PatupaaSettings::setToggles($payload['services']);
            }

            if (isset($payload['afa_price'])) {
                PatupaaSettings::setAfaPrice((float) $payload['afa_price']);
            }

            ServiceProvider::query()->update(['is_active' => false, 'is_default' => false]);

            $appliedProviders = [];

            foreach ((array) ($payload['providers'] ?? []) as $providerData) {
                if (! is_array($providerData) || empty($providerData['base_url'])) {
                    continue;
                }

                $provider = ServiceProvider::updateOrCreate(
                    ['base_url' => (string) $providerData['base_url']],
                    [
                        'name' => (string) ($providerData['name'] ?? 'Provider'),
                        'driver' => (string) ($providerData['driver'] ?? 'patupaa'),
                        'api_key' => (string) ($providerData['api_key'] ?? ''),
                        'api_secret' => (string) ($providerData['api_secret'] ?? ''),
                        'services' => $providerData['services'] ?? null,
                        'is_active' => true,
                        'sort_order' => (int) ($providerData['sort_order'] ?? 0),
                    ]
                );

                $appliedProviders[] = $provider;
            }

            if ($appliedProviders !== []) {
                $defaultBaseUrl = collect($payload['providers'] ?? [])->firstWhere('is_default', true)['base_url'] ?? null;
                $default = collect($appliedProviders)->firstWhere('base_url', $defaultBaseUrl) ?? $appliedProviders[0];
                ServiceProvider::makeDefault($default);
            }

            $package->update([
                'applied_count' => $package->applied_count + 1,
                'last_applied_at' => now(),
                'last_applied_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
            ]);

            SettingsService::setMany([
                self::APPLIED_KEY => json_encode([
                    'package_id' => $package->id,
                    'name' => $package->name,
                    'version' => $package->version,
                    'applied_at' => now()->toIso8601String(),
                    'applied_by' => $user->email,
                ]),
            ]);

            return [
                'package' => $package->refresh(),
                'providers' => count($appliedProviders),
            ];
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function applied(): ?array
    {
        $raw = SettingsService::get(self::APPLIED_KEY);
        $decoded = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;

        return is_array($decoded) ? $decoded : null;
    }
}
