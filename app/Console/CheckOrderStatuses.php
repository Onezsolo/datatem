<?php

namespace App\Console;

use App\Models\Purchase;
use App\Services\AffiliateService;
use App\Services\LegacyDataService;
use App\Services\NotifyService;
use App\Services\ResellerService;
use Illuminate\Support\Facades\Log;

/**
 * Invokable runnable by cron: fetch order status from API for all "processing" purchases and update DB.
 */
class CheckOrderStatuses
{
    public function __invoke(): void
    {
        $purchases = Purchase::with(['user', 'reseller'])
            ->where('status', 'processing')
            ->whereNotNull('transaction_id')
            ->where('transaction_id', '!=', '')
            ->orderBy('id')
            ->get();

        $resellerCount = $purchases->where('reseller_id', '!=', null)->count();
        Log::info('Schedule orders:check-status ran', ['processing_count' => $purchases->count(), 'reseller_purchases' => $resellerCount]);
        if ($resellerCount > 0) {
            Log::channel('storefront')->info('CheckOrderStatuses: processing reseller purchases', ['count' => $resellerCount, 'transaction_ids' => $purchases->whereNotNull('reseller_id')->pluck('transaction_id')->unique()->values()->all()]);
        }

        if ($purchases->isEmpty()) {
            return;
        }

        // Group by transaction_id to minimize API calls (one fetch per transaction).
        $byTid = $purchases->groupBy(function (Purchase $p) {
            return trim((string) $p->transaction_id);
        });

        foreach ($byTid as $tid => $group) {
            if ($tid === '') {
                continue;
            }
            $this->checkTransaction($tid, $group);
        }
    }

    /**
     * Fetch status for one transaction_id and update all purchases in this group.
     *
     * @param  string  $tid
     * @param  \Illuminate\Support\Collection<int, Purchase>  $purchases
     */
    private function checkTransaction(string $tid, $purchases): void
    {
        $result = LegacyDataService::fetchTransactionStatus($tid);
        if (! ($result['success'] ?? false)) {
            Log::debug('CheckOrderStatuses: fetch failed for txn ' . $tid, [
                'message' => $result['message'] ?? 'unknown',
            ]);
            $hasReseller = $purchases->whereNotNull('reseller_id')->isNotEmpty();
            if ($hasReseller) {
                Log::channel('storefront')->warning('CheckOrderStatuses: fetch failed for reseller transaction', ['transaction_id' => $tid, 'message' => $result['message'] ?? 'unknown']);
            }
            return;
        }

        $orderItems = $result['order_items'] ?? [];
        if (count($orderItems) === 0) {
            $hasReseller = $purchases->whereNotNull('reseller_id')->isNotEmpty();
            if ($hasReseller) {
                Log::channel('storefront')->warning('CheckOrderStatuses: no order_items for reseller transaction', ['transaction_id' => $tid]);
            }
            return;
        }

        foreach ($purchases as $purchase) {
            $beneficiaryNorm = preg_replace('/\s+/', '', (string) $purchase->beneficiary);
            $item = collect($orderItems)->first(function ($i) use ($beneficiaryNorm) {
                $num = preg_replace('/\s+/', '', (string) ($i['beneficiary_number'] ?? ''));
                return $num === $beneficiaryNorm;
            }) ?? $orderItems[0];

            $apiStatus = (string) ($item['status'] ?? '');
            $mappedStatus = LegacyDataService::mapApiStatusToOurStatus($apiStatus);

            $purchase->update(['status' => $mappedStatus]);

            if ($purchase->reseller_id !== null) {
                Log::channel('storefront')->info('CheckOrderStatuses: reseller purchase status updated', [
                    'purchase_id' => $purchase->id,
                    'reference' => $purchase->reference,
                    'reseller_id' => $purchase->reseller_id,
                    'mapped_status' => $mappedStatus,
                ]);
            }

            if (in_array($mappedStatus, ['delivered', 'failed'], true) && $purchase->user) {
                NotifyService::notifyUserPurchaseStatus($purchase->fresh());
            }
            if ($mappedStatus === 'delivered') {
                AffiliateService::creditCommissionForPurchase($purchase->fresh());
                if ($purchase->reseller_id !== null) {
                    Log::channel('storefront')->info('CheckOrderStatuses: crediting reseller profit for delivered purchase', ['purchase_id' => $purchase->id, 'reference' => $purchase->reference]);
                }
                ResellerService::creditProfitForPurchase($purchase->fresh());
            }
        }
    }
}
