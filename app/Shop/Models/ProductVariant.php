<?php

declare(strict_types=1);

namespace App\Shop\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property string $sku
 * @property float|null $price
 * @property float|null $compare_at_price
 * @property int $stock_quantity
 * @property int $low_stock_threshold
 * @property bool $track_inventory
 * @property array<string, mixed>|null $attributes
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, CartItem> $cartItems
 * @property-read float $effective_price
 * @property-read bool $has_discount
 * @property-read Collection<int, OrderItem> $orderItems
 * @property-read Product $product
 *
 * @method static Builder|ProductVariant active()
 * @method static ProductVariant create(array<string, mixed> $attributes = [])
 * @method static Builder|ProductVariant inStock()
 * @method static Builder|ProductVariant ordered()
 * @method static ProductVariant updateOrCreate(array<string, mixed> $attributes, array<string, mixed> $values = [])
 */
class ProductVariant extends Model
{
    protected $table = 'shop_product_variants';

    use HasFactory, SoftDeletes;

    protected $attributes = [
        'price' => null,
        'stock_quantity' => 0,
        'low_stock_threshold' => 5,
        'track_inventory' => true,
        'sort_order' => 0,
        'is_active' => true,
    ];

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'compare_at_price',
        'stock_quantity',
        'low_stock_threshold',
        'track_inventory',
        'attributes',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'track_inventory' => 'boolean',
        'attributes' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'variant_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'variant_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where('track_inventory', false)
                ->orWhere('stock_quantity', '>', 0);
        });
    }

    public function getHasDiscountAttribute(): bool
    {
        return $this->compare_at_price !== null && $this->compare_at_price > $this->price;
    }

    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->price ?? $this->product->price);
    }

    public function isInStock(): bool
    {
        if (! $this->track_inventory) {
            return true;
        }

        return $this->stock_quantity > 0;
    }
}
