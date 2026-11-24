<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TemplateLicense extends Model
{
    protected $fillable = [
        'license_key_hash',
        'key_prefix',
        'product_key',
        'license_tier',
        'price',
        'deployments_allowed',
        'support_ends_at',
        'client_name',
        'client_email',
        'domain',
        'agreement_reference',
        'issued_at',
        'expires_at',
        'status',
        'notes',
        'issued_by',
    ];

    protected $hidden = [
        'license_key_hash',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'support_ends_at' => 'datetime',
            'price' => 'decimal:2',
            'deployments_allowed' => 'integer',
        ];
    }

    public static function generatePlainKey(): string
    {
        $groups = [];

        for ($i = 0; $i < 4; $i++) {
            $groups[] = strtoupper(Str::random(4));
        }

        return 'DTMP-'.implode('-', $groups);
    }

    public static function normaliseKey(string $key): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/i', '', $key));
    }

    public static function hashKey(string $key): string
    {
        return hash('sha256', self::normaliseKey($key));
    }

    public static function prefixForKey(string $key): string
    {
        $normalised = self::normaliseKey($key);

        return substr($normalised, 0, 8).'…';
    }

    public static function findByPlainKey(string $key): ?self
    {
        return self::query()->where('license_key_hash', self::hashKey($key))->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function (Builder $query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ! $this->isExpired();
    }

    public function statusLabel(): string
    {
        if ($this->status === 'revoked') {
            return 'Revoked';
        }

        return $this->isExpired() ? 'Expired' : 'Active';
    }
}
