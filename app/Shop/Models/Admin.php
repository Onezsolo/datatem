<?php

declare(strict_types=1);

namespace App\Shop\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string $role
 * @property bool $is_active
 * @property bool $mfa_enabled
 * @property string|null $mfa_secret
 * @property array<int, string>|null $mfa_recovery_codes
 * @property Carbon|null $last_login_at
 * @property string|null $last_login_ip
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @method static Admin create(array<string, mixed> $attributes = [])
 */
class Admin extends Authenticatable
{
    protected $table = 'shop_admins';

    use HasFactory, Notifiable, SoftDeletes;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_CATALOG_MANAGER = 'catalog_manager';

    public const ROLE_ORDER_MANAGER = 'order_manager';

    public const ROLE_SUPPORT = 'support';

    protected $guard = 'admin';

    protected $attributes = [
        'role' => self::ROLE_ADMIN,
        'is_active' => true,
        'mfa_enabled' => false,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'mfa_enabled',
        'mfa_secret',
        'mfa_recovery_codes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
        'mfa_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'mfa_enabled' => 'boolean',
        'mfa_secret' => 'encrypted',
        'mfa_recovery_codes' => 'encrypted:array',
        'last_login_at' => 'datetime',
    ];

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function canManageCatalog(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_CATALOG_MANAGER,
        ], true);
    }

    public function canManageOrders(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_ORDER_MANAGER,
        ], true);
    }

    public function canAccessSupport(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_SUPPORT,
        ], true);
    }

    public function canViewOrders(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
            self::ROLE_ORDER_MANAGER,
            self::ROLE_SUPPORT,
        ], true);
    }

    public function canViewFinance(): bool
    {
        return in_array($this->role, [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_ADMIN,
        ], true);
    }

    public function canAuthenticate(): bool
    {
        return $this->is_active && ! $this->trashed();
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AdminAuditLog::class);
    }

    public function orderNotes(): HasMany
    {
        return $this->hasMany(OrderNote::class);
    }
}
