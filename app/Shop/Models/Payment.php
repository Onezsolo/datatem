<?php

declare(strict_types=1);

namespace App\Shop\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property string $reference
 * @property string $provider
 * @property string|null $provider_reference
 * @property float $amount
 * @property string $currency
 * @property string $status
 * @property string|null $payment_method
 * @property array<string, mixed>|null $provider_response
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $paid_at
 * @property Carbon|null $failed_at
 * @property Carbon|null $refunded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order $order
 *
 * @method static Payment create(array<string, mixed> $attributes = [])
 * @method static Builder|Payment failed()
 * @method static Builder|Payment paid()
 * @method static Builder|Payment pending()
 */
class Payment extends Model
{
    protected $table = 'shop_payments';

    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    protected $attributes = [
        'provider' => 'paystack',
        'currency' => 'GHS',
        'status' => self::STATUS_PENDING,
    ];

    protected $fillable = [
        'order_id',
        'reference',
        'provider',
        'provider_reference',
        'amount',
        'currency',
        'status',
        'payment_method',
        'provider_response',
        'meta',
        'paid_at',
        'failed_at',
        'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'provider_response' => 'array',
        'meta' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function markAsPaid(array $providerResponse = []): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'provider_response' => $providerResponse,
        ]);
    }

    public function markAsFailed(array $providerResponse = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'provider_response' => $providerResponse,
        ]);
    }
}
