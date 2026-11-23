<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    protected $fillable = [
        'user_id',
        'reseller_id',
        'reseller_profit',
        'data_package_id',
        'network',
        'data_plan',
        'beneficiary',
        'amount',
        'reference',
        'transaction_id',
        'failure_reason',
        'status',
        'affiliate_commission_credited',
        'reseller_profit_credited',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'reseller_profit' => 'decimal:2',
            'affiliate_commission_credited' => 'boolean',
            'reseller_profit_credited' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dataPackage(): BelongsTo
    {
        return $this->belongsTo(DataPackage::class, 'data_package_id');
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }
}
