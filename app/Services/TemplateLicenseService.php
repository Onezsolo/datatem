<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TemplateLicense;
use App\Models\User;
use Throwable;

final class TemplateLicenseService
{
    private const DEPLOYMENT_KEY = 'deployment_license_key_hash';
    private const DEPLOYMENT_PREFIX = 'deployment_license_prefix';

    /**
     * @param  array<string, mixed>  $data
     * @return array{license: TemplateLicense, key: string}
     */
    public function issue(array $data, ?User $issuer = null): array
    {
        $key = TemplateLicense::generatePlainKey();

        $license = TemplateLicense::create([
            'license_key_hash' => TemplateLicense::hashKey($key),
            'key_prefix' => TemplateLicense::prefixForKey($key),
            'client_name' => (string) $data['client_name'],
            'client_email' => $data['client_email'] ?? null,
            'domain' => $data['domain'] ?? null,
            'agreement_reference' => $data['agreement_reference'] ?? null,
            'issued_at' => now(),
            'expires_at' => $data['expires_at'] ?? null,
            'status' => 'active',
            'notes' => $data['notes'] ?? null,
            'issued_by' => $issuer?->id,
        ]);

        return ['license' => $license, 'key' => $key];
    }

    public function verify(string $key): ?TemplateLicense
    {
        $license = TemplateLicense::findByPlainKey($key);

        return $license !== null && $license->isActive() ? $license : null;
    }

    public function setDeploymentKey(string $key): ?TemplateLicense
    {
        $license = TemplateLicense::findByPlainKey($key);

        if ($license === null) {
            return null;
        }

        SettingsService::setMany([
            self::DEPLOYMENT_KEY => $license->license_key_hash,
            self::DEPLOYMENT_PREFIX => $license->key_prefix,
        ]);

        return $license;
    }

    /**
     * @return array{license: TemplateLicense|null, state: string, prefix: string|null}
     */
    public function deploymentStatus(): array
    {
        $hash = (string) SettingsService::get(self::DEPLOYMENT_KEY, '');
        $prefix = (string) SettingsService::get(self::DEPLOYMENT_PREFIX, '');

        if ($hash === '') {
            return ['license' => null, 'state' => 'missing', 'prefix' => null];
        }

        $license = TemplateLicense::query()->where('license_key_hash', $hash)->first();

        if ($license === null) {
            return ['license' => null, 'state' => 'invalid', 'prefix' => $prefix ?: null];
        }

        $state = match (true) {
            $license->status === 'revoked' => 'revoked',
            $license->isExpired() => 'expired',
            default => 'active',
        };

        return ['license' => $license, 'state' => $state, 'prefix' => $prefix ?: $license->key_prefix];
    }

    public function clearDeploymentKey(): void
    {
        try {
            SettingsService::setMany([
                self::DEPLOYMENT_KEY => '',
                self::DEPLOYMENT_PREFIX => '',
            ]);
        } catch (Throwable) {
            // Settings unavailable; nothing to clear.
        }
    }
}
