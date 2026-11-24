<?php

declare(strict_types=1);

namespace App\Shop\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $token
 * @property string|null $session_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int $item_count
 * @property-read float $subtotal
 * @property-read Collection<int, CartItem> $items
 *
 * @method static Builder|Cart expired()
 * @method static Builder|Cart valid()
 * @method static Cart create(array<string, mixed> $attributes = [])
 */
class Cart extends Model
{
    protected $table = 'shop_carts';

    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'session_id',
        'ip_address',
        'user_agent',
        'meta',
        'expires_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Cart $cart): void {
            if (empty($cart->token)) {
                $cart->token = Str::random(40);
            }

            if (empty($cart->expires_at)) {
                $cart->expires_at = Carbon::now()->addMinutes((int) config('cart.token_expiry', 1440));
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum(
            fn (CartItem $item): float => (float) $item->price * $item->quantity
        );
    }

    public function getItemCountAttribute(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isEmpty(): bool
    {
        return $this->item_count === 0;
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereNotNull('expires_at')
                ->where('expires_at', '<=', now());
        });
    }
}
