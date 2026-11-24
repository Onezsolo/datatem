<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceOrder extends Model
{
    protected $fillable = [
        'user_id',
        'reference',
        'remote_type',
        'catalog_item_id',
        'plan_code',
        'beneficiary',
        'customer_name',
        'address',
        'amount',
        'status',
        'payment_status',
        'remote_reference',
        'wallet_debited',
        'error_message',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'wallet_debited' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(PatupaaCatalogItem::class, 'catalog_item_id');
    }
}
