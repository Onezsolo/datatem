<?php

declare(strict_types=1);

namespace App\Shop\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $order_number
 * @property string $customer_name
 * @property string $customer_phone
 * @property string|null $customer_email
 * @property string $delivery_address
 * @property string $delivery_city
 * @property string|null $delivery_region
 * @property string|null $delivery_zone
 * @property string|null $delivery_phone
 * @property string|null $delivery_notes
 * @property float $subtotal
 * @property float $delivery_fee
 * @property float $service_charge
 * @property float $discount
 * @property float $total
 * @property string $currency
 * @property string $status
 * @property string $payment_status
 * @property string|null $payment_method
 * @property string|null $payment_reference
 * @property array<string, mixed>|null $payment_meta
 * @property Carbon|null $paid_at
 * @property Carbon|null $shipped_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, OrderItem> $items
 * @property-read Collection<int, OrderNote> $notes
 * @property-read Collection<int, Payment> $payments
 * @property-read Payment|null $successfulPayment
 *
 * @method static Order create(array<string, mixed> $attributes = [])
 * @method static Builder|Order paid()
 * @method static Builder|Order pending()
 */
class Order extends Model
{
    protected $table = 'shop_orders';

    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'payment_status' => 'pending',
        'currency' => 'GHS',
        'delivery_fee' => 0,
        'service_charge' => 0,
        'discount' => 0,
    ];

    protected $fillable = [
        'user_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_address',
        'delivery_city',
        'delivery_region',
        'delivery_zone',
        'delivery_phone',
        'delivery_notes',
        'subtotal',
        'delivery_fee',
        'service_charge',
        'discount',
        'total',
        'currency',
        'status',
        'payment_status',
        'payment_method',
        'payment_reference',
        'payment_meta',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
        'meta',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'payment_meta' => 'array',
        'meta' => 'array',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            if (empty($order->order_number)) {
                $order->order_number = 'DT-'.Str::upper(Str::random(8));
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function successfulPayment(): HasOne
    {
        $relation = $this->hasOne(Payment::class);
        $relation->where('status', 'paid');

        return $relation;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', 'paid');
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PAID], true);
    }

    public function markAsPaid(): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'payment_status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    public function markAsShipped(): void
    {
        $this->update([
            'status' => self::STATUS_SHIPPED,
            'shipped_at' => now(),
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    public function markAsRefunded(): void
    {
        $this->update([
            'status' => self::STATUS_REFUNDED,
            'payment_status' => 'refunded',
        ]);
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }
}
