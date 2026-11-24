<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_DEVELOPER = 'developer';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'delivery_name',
        'delivery_phone',
        'delivery_address',
        'delivery_city',
        'delivery_region',
        'delivery_zone',
        'delivery_notes',
        'balance',
        'password',
        'role',
        'reseller_slug',
        'store_name',
        'store_whatsapp',
        'store_slug',
        'referred_by_affiliate_id',
        'referrer_signup_bonus_paid',
    ];

    public function referredByAffiliate(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Affiliate::class, 'referred_by_affiliate_id');
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'decimal:2',
        'reseller_earnings_balance' => 'decimal:2',
        'referrer_signup_bonus_paid' => 'boolean',
    ];

    /**
     * Deduct from wallet for a purchase: balance always decreases by $amount;
     * reseller_earnings_balance decreases by the portion that came from store profit (spent first).
     */
    public function applyWalletSpend(float $amount): void
    {
        if ($amount <= 0) {
            return;
        }
        $fromReseller = min((float) $this->reseller_earnings_balance, $amount);
        if ($fromReseller > 0) {
            $this->decrement('reseller_earnings_balance', $fromReseller);
        }
        $this->decrement('balance', $amount);
    }

    /**
     * Check if user is admin.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isDeveloper(): bool
    {
        return $this->role === self::ROLE_DEVELOPER;
    }

    public function hasAdminAccess(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_DEVELOPER], true);
    }

    /**
     * Name for display (alias for full_name).
     */
    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['full_name'] ?? '');
    }

    /**
     * Whether the user is an admin (for Inertia/API).
     */
    public function getIsAdminAttribute(): bool
    {
        return $this->isAdmin();
    }

    public function resellerRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ResellerRequest::class);
    }

    public function latestResellerRequest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ResellerRequest::class)->latestOfMany();
    }

    public function resellerPackagePrices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ResellerPackagePrice::class, 'user_id');
    }

    public function isReseller(): bool
    {
        return ResellerRequest::where('user_id', $this->id)->where('status', 'approved')->exists();
    }

    public function affiliate(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Affiliate::class);
    }

    public function resellerWithdrawals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ResellerWithdrawal::class);
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
