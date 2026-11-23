<?php

namespace App\Services;

use App\Models\Purchase;
use Illuminate\Support\Facades\Log;

final class ResellerService
{
    /**
     * Credit reseller profit when a storefront purchase is delivered.
     * Safe to call multiple times: guarded by reseller_profit_credited on the purchase.
     */
    public static function creditProfitForPurchase(Purchase $purchase): void
    {
        $purchase->refresh();
        Log::channel('storefront')->info('ResellerService: creditProfitForPurchase', [
            'purchase_id' => $purchase->id,
            'reference' => $purchase->reference,
            'reseller_id' => $purchase->reseller_id,
            'reseller_profit' => $purchase->reseller_profit,
            'reseller_profit_credited' => $purchase->reseller_profit_credited,
        ]);
        if ($purchase->reseller_id === null || $purchase->reseller_profit === null || (float) $purchase->reseller_profit <= 0) {
            Log::channel('storefront')->info('ResellerService: skip (no reseller or no profit)', ['purchase_id' => $purchase->id]);
            return;
        }
        if ($purchase->reseller_profit_credited) {
            Log::channel('storefront')->info('ResellerService: skip (already credited)', ['purchase_id' => $purchase->id]);
            return;
        }

        $reseller = $purchase->reseller;
        if (! $reseller) {
            Log::channel('storefront')->warning('ResellerService: skip (reseller not found)', ['purchase_id' => $purchase->id, 'reseller_id' => $purchase->reseller_id]);
            return;
        }

        $amount = (float) $purchase->reseller_profit;
        $reseller->increment('balance', $amount);
        if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'reseller_earnings_balance')) {
            $reseller->increment('reseller_earnings_balance', $amount);
        }
        $purchase->update(['reseller_profit_credited' => true]);
        Log::channel('storefront')->info('ResellerService: profit credited', [
            'purchase_id' => $purchase->id,
            'reseller_id' => $reseller->id,
            'amount' => $amount,
            'new_balance' => $reseller->fresh()->balance,
        ]);
    }
}
