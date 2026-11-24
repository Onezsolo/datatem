<?php

declare(strict_types=1);

namespace App\Shop\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property int|null $variant_id
 * @property string $type
 * @property int $quantity
 * @property string|null $reason
 * @property string|null $reference
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Product $product
 * @property-read ProductVariant|null $variant
 *
 * @method static InventoryMovement create(array<string, mixed> $attributes = [])
 */
class InventoryMovement extends Model
{
    use HasFactory;

    protected $table = 'shop_inventory_movements';

    public const TYPE_RESTOCK = 'restock';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const TYPE_SALE = 'sale';

    public const TYPE_RETURN = 'return';

    protected $fillable = [
        'product_id',
        'variant_id',
        'type',
        'quantity',
        'reason',
        'reference',
        'meta',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'meta' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
