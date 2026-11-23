<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerPackagePrice extends Model
{
    protected $fillable = [
        'user_id',
        'data_package_id',
        'selling_price',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
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
}
