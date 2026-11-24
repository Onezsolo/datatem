<?php

declare(strict_types=1);

namespace App\Shop\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $short_description
 * @property float $price
 * @property float|null $compare_at_price
 * @property string $sku
 * @property int $stock_quantity
 * @property int $low_stock_threshold
 * @property bool $track_inventory
 * @property bool $is_active
 * @property bool $is_featured
 * @property float|null $weight
 * @property string $weight_unit
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, CartItem> $cartItems
 * @property-read Category $category
 * @property-read int|null $discount_percentage
 * @property-read bool $has_discount
 * @property-read Collection<int, ProductImage> $images
 * @property-read Collection<int, OrderItem> $orderItems
 * @property-read ProductImage|null $primaryImage
 * @property-read Collection<int, ProductVariant> $variants
 *
 * @method static Builder|Product active()
 * @method static Product create(array<string, mixed> $attributes = [])
 * @method static Builder|Product featured()
 * @method static Builder|Product inStock()
 * @method static Builder|Product search(string $term)
 * @method static Product updateOrCreate(array<string, mixed> $attributes, array<string, mixed> $values = [])
 */
class Product extends Model
{
    protected $table = 'shop_products';

    use HasFactory, SoftDeletes;

    protected $attributes = [
        'stock_quantity' => 0,
        'low_stock_threshold' => 5,
        'track_inventory' => true,
        'is_active' => true,
        'is_featured' => false,
        'weight_unit' => 'kg',
    ];

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'compare_at_price',
        'sku',
        'stock_quantity',
        'low_stock_threshold',
        'track_inventory',
        'is_active',
        'is_featured',
        'weight',
        'weight_unit',
        'meta',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'track_inventory' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'weight' => 'decimal:2',
        'meta' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primaryImage(): HasOne
    {
        $relation = $this->hasOne(ProductImage::class);
        $relation->where('is_primary', true);

        return $relation;
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function scopeWithOrderedImages(Builder $query): Builder
    {
        return $query->with(['images' => fn ($query) => $query->orderBy('sort_order')]);
    }

    public function scopeWithPrimaryImage(Builder $query): Builder
    {
        return $query->with('primaryImage');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_featured', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('track_inventory', false)
                ->orWhere('stock_quantity', '>', 0);
        });
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        return $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', $term)
                ->orWhere('sku', 'like', $term)
                ->orWhere('short_description', 'like', $term);
        });
    }

    public function getHasDiscountAttribute(): bool
    {
        return $this->compare_at_price !== null && $this->compare_at_price > $this->price;
    }

    public function getDiscountPercentageAttribute(): ?int
    {
        if (! $this->has_discount) {
            return null;
        }

        return (int) round((($this->compare_at_price - $this->price) / $this->compare_at_price) * 100);
    }

    public function isInStock(): bool
    {
        if (! $this->track_inventory) {
            return true;
        }

        return $this->stock_quantity > 0;
    }
}
