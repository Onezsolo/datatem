<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontPayment extends Model
{
    protected $table = 'storefront_payments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'reseller_id',
        'data_package_id',
        'beneficiary',
        'amount',
        'amount_charged',
        'reseller_profit',
        'base_amount',
        'status',
        'customer_email',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_charged' => 'decimal:2',
            'reseller_profit' => 'decimal:2',
            'base_amount' => 'decimal:2',
        ];
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    public function dataPackage(): BelongsTo
    {
        return $this->belongsTo(DataPackage::class, 'data_package_id');
    }
}
