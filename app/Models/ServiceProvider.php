<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Patupaa\PatupaaClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ServiceProvider extends Model
{
    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    protected $fillable = [
        'name',
        'driver',
        'base_url',
        'api_key',
        'api_secret',
        'services',
        'is_active',
        'is_default',
        'sort_order',
        'last_synced_at',
        'last_sync_error',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'api_secret' => 'encrypted',
            'services' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'last_synced_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function catalogItems(): HasMany
    {
        return $this->hasMany(PatupaaCatalogItem::class, 'provider_id');
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class, 'provider_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function supports(string $type): bool
    {
        $services = $this->services;

        if (! is_array($services) || $services === []) {
            return true;
        }

        return in_array($type, $services, true);
    }

    public function client(): PatupaaClient
    {
        if ($this->driver !== 'patupaa') {
            throw new \RuntimeException('Unsupported service provider driver ['.$this->driver.'].');
        }

        return PatupaaClient::forProvider($this);
    }

    public static function ensureDefault(): self
    {
        $default = self::query()->where('is_active', true)->where('is_default', true)->first()
            ?? self::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->first();

        if ($default !== null) {
            return $default;
        }

        return self::create([
            'name' => 'Default provider',
            'driver' => 'patupaa',
            'base_url' => (string) config('services.patupaa.base_url'),
            'api_key' => (string) config('services.patupaa.api_key'),
            'api_secret' => (string) config('services.patupaa.api_secret'),
            'services' => array_keys(\App\Services\Patupaa\PatupaaSettings::TYPES),
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 0,
        ]);
    }

    public static function makeDefault(self $provider): void
    {
        self::query()->whereKeyNot($provider->id)->update(['is_default' => false]);
        $provider->update(['is_default' => true, 'is_active' => true]);
    }

    public function maskedKey(): string
    {
        $key = (string) $this->api_key;

        return $key === '' ? '' : Str::substr($key, 0, 4).'…'.Str::substr($key, -4);
    }
}
