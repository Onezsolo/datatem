<?php

namespace App\Http\Controllers;

use App\Models\DataPackage;
use App\Models\Purchase;
use App\Models\User;
use App\Services\AffiliateService;
use App\Services\Refer2BundleService;
use App\Services\NotifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BuyDataController extends Controller
{
    /** Minutes before the same beneficiary number can be used for another data purchase. */
    public const BENEFICIARY_COOLDOWN_MINUTES = 5;

    /**
     * Show the Buy Data page (single + bulk). Packages from DB (synced from JayBart).
     */
    public function index(Request $request): Response|JsonResponse
    {
        $packages = DataPackage::available()
            ->excludeExpressNetworks()
            ->excludeProviderBundleIds()
            ->orderBy('network')
            ->orderBy('volume')
            ->get();

        // Sort so MTN is first in listing
        $networkOrder = function ($a, $b) {
            $order = ['MTN' => 0, 'AT' => 1, 'AirtelTigo' => 1, 'Telecel' => 2];
            $na = $a->network ?? '';
            $nb = $b->network ?? '';
            $ia = 3;
            $ib = 3;
            foreach ($order as $net => $i) {
                if (stripos($na, $net) !== false) { $ia = $i; break; }
            }
            foreach ($order as $net => $i) {
                if (stripos($nb, $net) !== false) { $ib = $i; break; }
            }
            return $ia <=> $ib ?: strcasecmp($na, $nb);
        };
        $packages = $packages->sort($networkOrder)->values();

        $networks = $packages->pluck('network')->unique()->filter()->values()->all();
        $networkSortOrder = ['MTN' => 0, 'AT' => 1, 'AirtelTigo' => 1, 'Telecel' => 2];
        usort($networks, function ($a, $b) use ($networkSortOrder) {
            $ia = 3;
            $ib = 3;
            foreach ($networkSortOrder as $net => $i) {
                if (stripos($a, $net) !== false) { $ia = $i; break; }
            }
            foreach ($networkSortOrder as $net => $i) {
                if (stripos($b, $net) !== false) { $ib = $i; break; }
            }
            return $ia <=> $ib ?: strcasecmp($a, $b);
        });
        $packagesByNetwork = [];
        $packagesList = [];
        foreach ($packages as $p) {
            $price = $p->selling_price !== null ? (float) $p->selling_price : (float) $p->console_price;
            $planLabel = ($p->volume_gb ?: $p->volume . ' GB') . ' – ₵' . number_format($price, 2);
            $item = [
                'id' => $p->id,
                'external_id' => $p->external_id,
                'network' => $p->network ?? '',
                'plan_name' => $planLabel,
                'volume_gb' => $p->volume_gb ?: $p->volume . ' GB',
                'price' => $price,
            ];
            $packagesByNetwork[$p->network][] = $item;
            $packagesList[] = $item;
        }

        if ($request->is('api/*')) {
            return response()->json([
                'networks' => $networks,
                'packages_by_network' => $packagesByNetwork,
                'packages' => $packagesList,
            ]);
        }

        return Inertia::render('BuyData/Index', [
            'networks' => $networks,
            'packagesByNetwork' => $packagesByNetwork,
        ]);
    }

    /**
     * Validate Ghana mobile number (10 digits, 02/03/05).
     */
    private function validGhanaNumber(string $number): bool
    {
        $digits = preg_replace('/\s+/', '', $number);
        return (bool) preg_match('/^0[235]\d{8}$/', $digits);
    }

    /**
     * Whether this beneficiary had an order (any status) for the same package in the last N minutes.
     * Same number can rebuy the same package again after 5 minutes.
     */
    private function hasRecentPurchaseForBeneficiaryAndPackage(string $beneficiary, DataPackage $package): bool
    {
        $since = now()->subMinutes(self::BENEFICIARY_COOLDOWN_MINUTES);
        $query = DB::table('purchases')
            ->where('beneficiary', $beneficiary)
            ->where('created_at', '>=', $since);

        if (\Schema::hasColumn('purchases', 'data_package_id')) {
            $query->where('data_package_id', $package->id);
        } else {
            $planLabel = $package->volume_gb ?: ($package->volume . ' GB');
            $query->where('network', $package->network)->where('data_plan', $planLabel);
        }

        return $query->exists();
    }

    /**
     * Whether the API message indicates the package is not found or out of stock on the provider side.
     */
    private function isProviderOutOfStockMessage(string $message): bool
    {
        $m = strtolower($message);
        return str_contains($m, 'no package found') || str_contains($m, 'out of stock');
    }

    /**
     * Resolve selling price from package (admin-set or console fallback). Never trust client amount.
     */
    private function packagePrice(DataPackage $package): float
    {
        $price = $package->selling_price !== null ? (float) $package->selling_price : (float) $package->console_price;
        return round($price, 2);
    }

    /**
     * Map our package volume to JayBart shared_bundle (e.g. 10GB => 10000 per API example).
     */
    private function packageToSharedBundle(DataPackage $package): int
    {
        $volume = (float) $package->volume;
        if ($volume <= 0) {
            return 0;
        }
        return (int) round($volume * 1000);
    }

    /**
     * Fetch transaction status from API and update the purchase status (do not assume delivered).
     */
    private function fetchAndUpdatePurchaseStatus(string $transactionId, string $beneficiary): void
    {
        $tid = trim($transactionId);
        if ($tid === '') {
            return;
        }

        $result = Refer2BundleService::fetchTransactionStatus($tid);
        if (! ($result['success'] ?? false)) {
            return;
        }

        $orderItems = $result['order_items'] ?? [];
        if (count($orderItems) === 0) {
            return;
        }

        $beneficiaryNorm = preg_replace('/\s+/', '', $beneficiary);
        $item = collect($orderItems)->first(function ($i) use ($beneficiaryNorm) {
            $num = preg_replace('/\s+/', '', (string) ($i['beneficiary_number'] ?? ''));
            return $num === $beneficiaryNorm;
        }) ?? $orderItems[0];

        $apiStatus = (string) ($item['status'] ?? '');
        $mappedStatus = Refer2BundleService::mapApiStatusToOurStatus($apiStatus);

        DB::table('purchases')
            ->where('transaction_id', $tid)
            ->where('beneficiary', $beneficiary)
            ->update(['status' => $mappedStatus, 'updated_at' => now()]);

        if (in_array($mappedStatus, ['delivered', 'failed'], true)) {
            $purchase = Purchase::where('transaction_id', $tid)
                ->where('beneficiary', $beneficiary)
                ->with('user')
                ->first();
            if ($purchase && $purchase->user) {
                NotifyService::notifyUserPurchaseStatus($purchase);
            }
            if ($mappedStatus === 'delivered' && $purchase) {
                AffiliateService::creditCommissionForPurchase($purchase->fresh());
            }
        }
    }

    /**
     * Single data purchase: deduct balance (admin-set price), call JayBart, record. No client amount accepted.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'package_id' => 'required|integer|exists:data_packages,id',
            'beneficiary' => 'required|string|max:20',
        ]);

        $beneficiary = preg_replace('/\s+/', '', $data['beneficiary']);
        if (! $this->validGhanaNumber($beneficiary)) {
            throw ValidationException::withMessages([
                'beneficiary' => ['Please enter a valid Ghana mobile number (e.g. 0551234567).'],
            ]);
        }

        $package = DataPackage::available()
            ->excludeExpressNetworks()
            ->excludeProviderBundleIds()
            ->where('id', $data['package_id'])
            ->first();
        if (! $package) {
            throw ValidationException::withMessages([
                'package_id' => ['No package found or it is out of stock. Please choose another plan.'],
            ]);
        }

        if ($this->hasRecentPurchaseForBeneficiaryAndPackage($beneficiary, $package)) {
            throw ValidationException::withMessages([
                'beneficiary' => ['This number already had this bundle in the last ' . self::BENEFICIARY_COOLDOWN_MINUTES . ' minutes. You can rebuy the same plan after 5 minutes.'],
            ]);
        }

        $price = $this->packagePrice($package);
        $user = $request->user();

        $networkId = (int) ($package->network_id ?? 0);
        $sharedBundle = $this->packageToSharedBundle($package);
        if ($networkId < 1 || $sharedBundle < 1) {
            throw ValidationException::withMessages([
                'package_id' => ['This plan cannot be purchased (network or volume not set). Please sync packages in Admin or choose another plan.'],
            ]);
        }

        $result = DB::transaction(function () use ($user, $package, $price, $beneficiary) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            if (! $lockedUser) {
                return ['ok' => false, 'error' => 'User not found.'];
            }
            $balance = (float) $lockedUser->balance;
            if ($balance < $price) {
                return ['ok' => false, 'error' => 'Insufficient balance. You need ' . number_format($price, 2) . ' GHS.'];
            }

            $lockedUser->applyWalletSpend((float) $price);

            $reference = 'REF-' . strtoupper(uniqid());
            $planLabel = ($package->volume_gb ?: $package->volume . ' GB');
            $networkId = (int) ($package->network_id ?? 0);
            $sharedBundle = $this->packageToSharedBundle($package);
            $dataPlanOverride = $package->volume_gb ? trim((string) $package->volume_gb) : null;
            $providerBundleId = $package->provider_bundle_id ? trim((string) $package->provider_bundle_id) : null;
            $networkNameOverride = $package->network ? trim((string) $package->network) : null;
            \Illuminate\Support\Facades\Log::info('BuyData order', [
                'package_id' => $package->id,
                'volume_gb' => $package->volume_gb,
                'console_price' => $package->console_price,
                'data_plan_sent' => $dataPlanOverride ?? '(derived)',
                'bundle_id_sent' => $providerBundleId,
                'network_sent' => $networkNameOverride ?? '(from network_id)',
            ]);
            $apiResult = Refer2BundleService::buyPackage($beneficiary, $networkId, $sharedBundle, $dataPlanOverride, $providerBundleId, $networkNameOverride);

            if ($apiResult['success']) {
                $transactionCode = $apiResult['transaction_code'] ?? null;
                $orderStatus = $apiResult['order_status'] ?? null;
                $status = 'processing';
                if ($orderStatus !== null && $orderStatus !== '') {
                    $status = Refer2BundleService::mapApiStatusToOurStatus($orderStatus);
                }
                $insert = [
                    'user_id' => $user->id,
                    'network' => $package->network,
                    'data_plan' => $planLabel,
                    'beneficiary' => $beneficiary,
                    'amount' => $price,
                    'reference' => $reference,
                    'transaction_id' => $transactionCode,
                    'failure_reason' => null,
                    'status' => $status,
                    'created_at' => now(),
                ];
                if (\Schema::hasColumn('purchases', 'data_package_id')) {
                    $insert['data_package_id'] = $package->id;
                }
                DB::table('purchases')->insert($insert);
                if ($status === 'processing') {
                    $this->fetchAndUpdatePurchaseStatus($transactionCode ?? '', $beneficiary);
                } elseif (in_array($status, ['delivered', 'failed'], true)) {
                    $purchase = Purchase::where('reference', $reference)->where('beneficiary', $beneficiary)->with('user')->first();
                    if ($purchase && $purchase->user) {
                        NotifyService::notifyUserPurchaseStatus($purchase->fresh());
                    }
                    if ($status === 'delivered' && $purchase) {
                        AffiliateService::creditCommissionForPurchase($purchase->fresh());
                    }
                }
                NotifyService::admins(
                    'Data order',
                    $user->name . ' purchased ' . $planLabel . ' for ' . $beneficiary . '. Ref: ' . $reference . ', ' . number_format($price, 2) . ' GHS.',
                    'info',
                    url()->route('admin.orders')
                );
                return ['ok' => true, 'reference' => $reference, 'sent_to_api' => true];
            }

            // API failed: save as pending so it can be exported and sent manually
            $insert = [
                'user_id' => $user->id,
                'network' => $package->network,
                'data_plan' => $planLabel,
                'beneficiary' => $beneficiary,
                'amount' => $price,
                'reference' => $reference,
                'transaction_id' => null,
                'failure_reason' => $apiResult['message'] ?? 'API call failed',
                'status' => 'pending',
                'created_at' => now(),
            ];
            if (\Schema::hasColumn('purchases', 'data_package_id')) {
                $insert['data_package_id'] = $package->id;
            }
            DB::table('purchases')->insert($insert);
            NotifyService::admins(
                'Data order (not sent to API)',
                $user->name . ' ordered ' . $planLabel . ' for ' . $beneficiary . '. Ref: ' . $reference . '. API failed – export from Admin to send manually.',
                'info',
                url()->route('admin.orders')
            );
            return ['ok' => true, 'reference' => $reference, 'sent_to_api' => false];
        });

        if (! ($result['ok'] ?? false)) {
            throw ValidationException::withMessages([
                'beneficiary' => [$result['error'] ?? 'Purchase failed.'],
            ]);
        }

        $planLabel = $package->volume_gb ?: ($package->volume ?? '') . ' GB';
        NotifyService::notifyUserOrderPlaced($user, $result['reference'], $planLabel, $beneficiary, $price);

        $msg = ($result['sent_to_api'] ?? false)
            ? 'Data sent. Reference: ' . $result['reference']
            : 'Order received. Reference: ' . $result['reference'] . '. We\'ll process it shortly.';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $msg,
                'reference' => $result['reference'] ?? null,
                'sent_to_api' => $result['sent_to_api'] ?? false,
            ], 201);
        }
        return redirect()->route('orders.index')->with('success', $msg);
    }

    /**
     * Bulk data purchases: same package for all numbers; deduct balance per order, call JayBart each time.
     */
    public function storeBulk(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'package_id' => 'required|integer|exists:data_packages,id',
            'beneficiaries' => 'required|string|min:1',
        ]);

        $lines = array_filter(array_map('trim', preg_split('/[\r\n]+/', $data['beneficiaries'])));
        $numbers = [];
        foreach ($lines as $line) {
            $num = preg_replace('/\s+/', '', $line);
            if ($num === '') {
                continue;
            }
            if (! $this->validGhanaNumber($num)) {
                throw ValidationException::withMessages([
                    'beneficiaries' => ['Invalid number: ' . $line . '. Use Ghana format (e.g. 0551234567).'],
                ]);
            }
            $numbers[] = $num;
        }

        if (count($numbers) === 0) {
            throw ValidationException::withMessages([
                'beneficiaries' => ['Enter at least one valid beneficiary number (one per line).'],
            ]);
        }

        $package = DataPackage::available()
            ->excludeExpressNetworks()
            ->excludeProviderBundleIds()
            ->where('id', $data['package_id'])
            ->first();
        if (! $package) {
            throw ValidationException::withMessages([
                'package_id' => ['No package found or it is out of stock. Please choose another plan.'],
            ]);
        }

        $onCooldown = [];
        foreach ($numbers as $num) {
            if ($this->hasRecentPurchaseForBeneficiaryAndPackage($num, $package)) {
                $onCooldown[] = $num;
            }
        }
        if (count($onCooldown) > 0) {
            $list = implode(', ', $onCooldown);
            throw ValidationException::withMessages([
                'beneficiaries' => ['These numbers already had this bundle in the last ' . self::BENEFICIARY_COOLDOWN_MINUTES . ' minutes. You can rebuy after 5 minutes: ' . $list],
            ]);
        }

        $price = $this->packagePrice($package);
        $user = $request->user();
        $networkId = (int) ($package->network_id ?? 0);
        $sharedBundle = $this->packageToSharedBundle($package);
        if ($networkId < 1 || $sharedBundle < 1) {
            throw ValidationException::withMessages([
                'package_id' => ['This plan cannot be purchased (network or volume not set). Please sync packages in Admin or choose another plan.'],
            ]);
        }

        $planLabel = ($package->volume_gb ?: $package->volume . ' GB');
        $sentCount = 0;
        $pendingCount = 0;
        $failCount = 0;
        $networkId = (int) ($package->network_id ?? 0);
        $sharedBundle = $this->packageToSharedBundle($package);

        foreach ($numbers as $beneficiary) {
            $result = DB::transaction(function () use ($user, $package, $price, $beneficiary, $planLabel, $networkId, $sharedBundle) {
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
                if (! $lockedUser) {
                    return ['ok' => false];
                }
                $balance = (float) $lockedUser->balance;
                if ($balance < $price) {
                    return ['ok' => false, 'error' => 'Insufficient balance for ' . $beneficiary . '. Need ' . number_format($price, 2) . ' GHS.'];
                }

                $lockedUser->applyWalletSpend((float) $price);
                $reference = 'REF-' . strtoupper(uniqid());
                $dataPlanOverride = $package->volume_gb ? trim((string) $package->volume_gb) : null;
                $providerBundleId = $package->provider_bundle_id ? trim((string) $package->provider_bundle_id) : null;
                $networkNameOverride = $package->network ? trim((string) $package->network) : null;
                $apiResult = Refer2BundleService::buyPackage($beneficiary, $networkId, $sharedBundle, $dataPlanOverride, $providerBundleId, $networkNameOverride);

                if ($apiResult['success']) {
                    $transactionCode = $apiResult['transaction_code'] ?? null;
                    $insert = [
                        'user_id' => $user->id,
                        'network' => $package->network,
                        'data_plan' => $planLabel,
                        'beneficiary' => $beneficiary,
                        'amount' => $price,
                        'reference' => $reference,
                        'transaction_id' => $transactionCode,
                        'failure_reason' => null,
                        'status' => 'processing',
                        'created_at' => now(),
                    ];
                    if (\Schema::hasColumn('purchases', 'data_package_id')) {
                        $insert['data_package_id'] = $package->id;
                    }
                    DB::table('purchases')->insert($insert);
                    $this->fetchAndUpdatePurchaseStatus($transactionCode ?? '', $beneficiary);
                    return ['ok' => true, 'sent_to_api' => true];
                }

                $insert = [
                    'user_id' => $user->id,
                    'network' => $package->network,
                    'data_plan' => $planLabel,
                    'beneficiary' => $beneficiary,
                    'amount' => $price,
                    'reference' => $reference,
                    'transaction_id' => null,
                    'failure_reason' => $apiResult['message'] ?? 'API call failed',
                    'status' => 'pending',
                    'created_at' => now(),
                ];
                if (\Schema::hasColumn('purchases', 'data_package_id')) {
                    $insert['data_package_id'] = $package->id;
                }
                DB::table('purchases')->insert($insert);
                return ['ok' => true, 'sent_to_api' => false];
            });
            if ($result['ok'] ?? false) {
                if ($result['sent_to_api'] ?? false) {
                    $sentCount++;
                } else {
                    $pendingCount++;
                }
            } else {
                $failCount++;
            }
        }

        $message = $sentCount . ' order(s) sent.';
        if ($pendingCount > 0) {
            $message .= ' ' . $pendingCount . ' saved as pending (not sent to API – export from Admin to send manually).';
        }
        if ($failCount > 0) {
            $message .= ' ' . $failCount . ' skipped (insufficient balance).';
        }
        NotifyService::admins(
            'Bulk data orders',
            $user->name . ': ' . $message,
            'info',
            url()->route('admin.orders')
        );

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $message,
                'sent_count' => $sentCount,
                'pending_count' => $pendingCount,
                'fail_count' => $failCount,
            ], 201);
        }

        return redirect()->route('orders.index')->with('success', $message);
    }
}
