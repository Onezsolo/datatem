<?php

declare(strict_types=1);

namespace App\Shop\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $cart_id
 * @property int $product_id
 * @property int|null $variant_id
 * @property int $quantity
 * @property float $price
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Cart $cart
 * @property-read Product $product
 * @property-read float $total_price
 * @property-read ProductVariant|null $variant
 *
 * @method static CartItem create(array<string, mixed> $attributes = [])
 */
class CartItem extends Model
{
    protected $table = 'shop_cart_items';

    use HasFactory;

    protected $attributes = [
        'quantity' => 1,
    ];

    protected $fillable = [
        'cart_id',
        'product_id',
        'variant_id',
        'quantity',
        'price',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'meta' => 'array',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function getTotalPriceAttribute(): float
    {
        return (float) $this->price * $this->quantity;
    }
}
