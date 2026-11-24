<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PatupaaCatalogItem extends Model
{
    public const TYPE_DATA = 'data_bundle';

    public const TYPE_AIRTIME = 'airtime';

    public const TYPE_DIGITAL = 'digital';

    public const TYPE_WAEC = 'waec';

    public const TYPE_UTILITY = 'utility';

    protected $fillable = [
        'provider_id',
        'remote_type',
        'remote_id',
        'plan_code',
        'name',
        'network',
        'data_value',
        'validity',
        'price',
        'meta',
        'remote_enabled',
        'admin_enabled',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'meta' => 'array',
            'remote_enabled' => 'boolean',
            'admin_enabled' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('remote_enabled', true)->where('admin_enabled', true);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('remote_type', $type);
    }

    public function scopeFromActiveProvider(Builder $query, string $type): Builder
    {
        $ids = ServiceProvider::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (ServiceProvider $provider) => $provider->supports($type))
            ->pluck('id')
            ->all();

        return $query->whereIn('provider_id', $ids);
    }

    public function provider(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }

    public function isAvailable(): bool
    {
        return $this->remote_enabled && $this->admin_enabled;
    }
}
