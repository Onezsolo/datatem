<?php

declare(strict_types=1);

namespace App\Shop\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $icon
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Product> $activeProducts
 * @property-read Collection<int, Product> $products
 *
 * @method static Builder|Category active()
 * @method static Category create(array<string, mixed> $attributes = [])
 * @method static Builder|Category ordered()
 * @method static Category updateOrCreate(array<string, mixed> $attributes, array<string, mixed> $values = [])
 * @method static Builder|Category withActiveProducts()
 */
class Category extends Model
{
    protected $table = 'shop_categories';

    use HasFactory, SoftDeletes;

    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true,
    ];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function activeProducts(): HasMany
    {
        $relation = $this->hasMany(Product::class);
        $relation->where('is_active', true);

        return $relation;
    }

    public function scopeWithActiveProducts(Builder $query): Builder
    {
        return $query->whereHas(
            'products',
            fn (Builder $query): Builder => $query->where('is_active', true)
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
