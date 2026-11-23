<?php

namespace App\Http\Controllers;

use App\Models\DataPackage;
use App\Models\Purchase;
use App\Models\ResellerPackagePrice;
use App\Models\ResellerRequest;
use App\Models\ResellerWithdrawal;
use App\Models\User;
use App\Services\NotifyService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ResellerController extends Controller
{
    /**
     * Get current user's reseller request status (latest request if any).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $latest = ResellerRequest::where('user_id', $user->id)->latest()->first();

        $data = [
            'has_request' => $latest !== null,
            'request' => null,
        ];

        if ($latest) {
            $data['request'] = [
                'id' => $latest->id,
                'status' => $latest->status,
                'message' => $latest->message,
                'admin_notes' => $latest->admin_notes,
                'created_at' => $latest->created_at->toIso8601String(),
                'reviewed_at' => $latest->reviewed_at?->toIso8601String(),
            ];
        }

        if ($user->isReseller()) {
            $data['reseller_slug'] = $user->reseller_slug;
            $data['store_slug'] = $user->store_slug ? (string) $user->store_slug : null;
            $data['store_url'] = url('/store/' . (trim((string) $user->store_slug) !== '' ? $user->store_slug : $user->reseller_slug));
            $data['store_name'] = $user->store_name ? (string) $user->store_name : null;
            $data['store_whatsapp'] = $user->store_whatsapp ? (string) $user->store_whatsapp : null;
            $data['store_setup_complete'] = ! empty(trim((string) $user->store_slug)) && ! empty(trim((string) $user->store_name)) && ! empty(trim((string) $user->store_whatsapp));
            $stats = Purchase::where('reseller_id', $user->id)
                ->selectRaw('COALESCE(SUM(amount), 0) as total_sales, COALESCE(SUM(COALESCE(reseller_profit, 0)), 0) as total_profit')
                ->first();
            $data['total_sales'] = round((float) ($stats->total_sales ?? 0), 2);
            $data['total_profit'] = round((float) ($stats->total_profit ?? 0), 2);

            $recentSales = Purchase::where('reseller_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(30)
                ->get(['id', 'reference', 'network', 'data_plan', 'beneficiary', 'amount', 'reseller_profit', 'status', 'created_at'])
                ->map(function (Purchase $p) {
                    return [
                        'id' => $p->id,
                        'reference' => $p->reference,
                        'network' => $p->network,
                        'data_plan' => $p->data_plan,
                        'beneficiary' => $p->beneficiary,
                        'amount' => round((float) $p->amount, 2),
                        'reseller_profit' => $p->reseller_profit !== null ? round((float) $p->reseller_profit, 2) : 0,
                        'status' => $p->status,
                        'created_at' => $p->created_at?->toIso8601String(),
                    ];
                });
            $data['recent_sales'] = $recentSales->values()->all();

            if (Schema::hasTable('reseller_withdrawals')) {
                $data['wallet_balance'] = round((float) $user->balance, 2);
                $data['reseller_withdrawable_balance'] = Schema::hasColumn('users', 'reseller_earnings_balance')
                    ? round(max(0, (float) $user->reseller_earnings_balance), 2)
                    : $data['wallet_balance'];
                $data['min_withdraw'] = (float) (SettingsService::get('min_withdraw', '50') ?: 50);
                $data['has_pending_withdrawal'] = ResellerWithdrawal::where('user_id', $user->id)
                    ->where('status', ResellerWithdrawal::STATUS_PENDING)
                    ->exists();
                $data['withdrawals'] = ResellerWithdrawal::where('user_id', $user->id)
                    ->orderByDesc('created_at')
                    ->limit(50)
                    ->get()
                    ->map(fn ($w) => [
                        'id' => $w->id,
                        'amount' => round((float) $w->amount, 2),
                        'momo_number' => $w->momo_number ? (string) $w->momo_number : null,
                        'fee_amount' => $w->fee_amount !== null ? round((float) $w->fee_amount, 2) : null,
                        'status' => $w->status,
                        'admin_notes' => $w->admin_notes,
                        'created_at' => $w->created_at?->toIso8601String(),
                        'processed_at' => $w->processed_at?->toIso8601String(),
                    ])
                    ->values()
                    ->all();
            }
        }

        return response()->json($data);
    }

    /**
     * Request a wallet withdrawal (approved resellers). Uses the same min withdrawal and fee settings as affiliate payouts.
     */
    public function requestWithdrawal(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isReseller()) {
            return response()->json(['error' => 'You must be an approved reseller to request a withdrawal.'], 403);
        }
        if (! Schema::hasTable('reseller_withdrawals')) {
            return response()->json(['error' => 'Withdrawals are not available yet.'], 503);
        }

        $storeSetupComplete = ! empty(trim((string) $user->store_slug))
            && ! empty(trim((string) $user->store_name))
            && ! empty(trim((string) $user->store_whatsapp));
        if (! $storeSetupComplete) {
            return response()->json(['error' => 'Complete your store setup on the reseller page before requesting a withdrawal.'], 422);
        }

        $minWithdraw = (float) (SettingsService::get('min_withdraw', '50') ?: 50);
        $data = $request->validate([
            'amount' => 'required|numeric|min:' . $minWithdraw,
            'momo_number' => 'required|string|max:40',
        ]);

        $amount = round((float) $data['amount'], 2);
        $momo = self::normalizeGhMomoNumber($data['momo_number']);
        if ($momo === null) {
            throw ValidationException::withMessages([
                'momo_number' => ['Enter a valid Ghana mobile money number (e.g. 024XXXXXXX or 233XXXXXXXXX).'],
            ]);
        }

        try {
            $withdrawal = DB::transaction(function () use ($user, $amount, $momo) {
                $locked = User::whereKey($user->id)->lockForUpdate()->first();
                if (! $locked) {
                    throw new \RuntimeException('user_missing');
                }
                if (! ResellerRequest::where('user_id', $locked->id)->where('status', 'approved')->exists()) {
                    throw new \RuntimeException('not_reseller');
                }
                if (ResellerWithdrawal::where('user_id', $locked->id)->where('status', ResellerWithdrawal::STATUS_PENDING)->exists()) {
                    throw new \RuntimeException('already_pending');
                }
                $withdrawable = Schema::hasColumn('users', 'reseller_earnings_balance')
                    ? (float) $locked->reseller_earnings_balance
                    : (float) $locked->balance;
                if ($amount > $withdrawable) {
                    throw new \RuntimeException('insufficient');
                }

                return ResellerWithdrawal::create([
                    'user_id' => $locked->id,
                    'amount' => $amount,
                    'momo_number' => $momo,
                    'status' => ResellerWithdrawal::STATUS_PENDING,
                ]);
            });
        } catch (\RuntimeException $e) {
            return match ($e->getMessage()) {
                'user_missing' => response()->json(['error' => 'Account not found.'], 404),
                'not_reseller' => response()->json(['error' => 'You must be an approved reseller to request a withdrawal.'], 403),
                'already_pending' => response()->json(['error' => 'You already have a pending withdrawal request.'], 422),
                'insufficient' => response()->json([
                    'error' => 'Insufficient reseller earnings to withdraw. Withdrawable (store profit): GHS '
                        . number_format(
                            Schema::hasColumn('users', 'reseller_earnings_balance')
                                ? max(0, (float) $user->fresh()->reseller_earnings_balance)
                                : (float) $user->fresh()->balance,
                            2
                        )
                        . '. Your full wallet may include top-ups — only profit is withdrawable.',
                ], 422),
                default => throw $e,
            };
        }

        NotifyService::notifyAdminsResellerWithdrawalRequested($withdrawal);

        return response()->json([
            'message' => 'Withdrawal request submitted. You will be notified when it is processed.',
            'withdrawal' => [
                'id' => $withdrawal->id,
                'amount' => $withdrawal->amount,
                'momo_number' => $withdrawal->momo_number ? (string) $withdrawal->momo_number : null,
                'status' => $withdrawal->status,
                'created_at' => $withdrawal->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Submit a new reseller request. Only allowed if user has no pending request.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $pending = ResellerRequest::where('user_id', $user->id)->where('status', ResellerRequest::STATUS_PENDING)->exists();
        if ($pending) {
            throw ValidationException::withMessages([
                'message' => ['You already have a pending reseller request. Wait for a response.'],
            ]);
        }

        $data = $request->validate([
            'message' => 'nullable|string|max:2000',
        ]);

        $resellerRequest = ResellerRequest::create([
            'user_id' => $user->id,
            'status' => ResellerRequest::STATUS_PENDING,
            'message' => $data['message'] ?? null,
        ]);

        $resellerRequest->load('user');
        \App\Services\NotifyService::notifyAdminsNewResellerRequest($resellerRequest);
        \App\Services\NotifyService::notifyUserResellerRequestSent($user);

        return response()->json([
            'message' => 'Reseller request submitted successfully.',
            'request' => [
                'id' => $resellerRequest->id,
                'status' => $resellerRequest->status,
                'message' => $resellerRequest->message,
                'created_at' => $resellerRequest->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * List packages with admin (base) price and reseller's custom price. Only for approved resellers.
     */
    public function packages(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isReseller()) {
            return response()->json(['error' => 'You must be an approved reseller to set prices.'], 403);
        }

        $packages = DataPackage::available()->excludeExpressNetworks()->excludeProviderBundleIds()->orderBy('network')->orderBy('volume')->get();
        $resellerPrices = ResellerPackagePrice::where('user_id', $user->id)
            ->get()
            ->keyBy('data_package_id');

        $list = $packages->map(function (DataPackage $p) use ($resellerPrices) {
            $basePrice = $p->selling_price !== null ? (float) $p->selling_price : (float) $p->console_price;
            $custom = $resellerPrices->get($p->id);
            $sellingPrice = $custom ? (float) $custom->selling_price : $basePrice;
            return [
                'id' => $p->id,
                'network' => $p->network ?? '',
                'volume_gb' => $p->volume_gb ?: $p->volume . ' GB',
                'base_price' => round($basePrice, 2),
                'selling_price' => round($sellingPrice, 2),
                'your_profit' => round($sellingPrice - $basePrice, 2),
            ];
        });

        return response()->json(['packages' => $list]);
    }

    /**
     * Save reseller's selling price for a package. Must be >= base (admin) price.
     */
    public function savePrice(Request $request, $packageId): JsonResponse
    {
        $user = $request->user();
        if (! $user->isReseller()) {
            return response()->json(['error' => 'You must be an approved reseller to set prices.'], 403);
        }

        $package = DataPackage::available()->find($packageId);
        if (! $package) {
            return response()->json(['error' => 'Package not found.'], 404);
        }

        $basePrice = $package->selling_price !== null ? (float) $package->selling_price : (float) $package->console_price;
        $data = $request->validate([
            'selling_price' => 'required|numeric|min:' . $basePrice . '|max:9999',
        ]);

        $sellingPrice = round((float) $data['selling_price'], 2);
        ResellerPackagePrice::updateOrCreate(
            ['user_id' => $user->id, 'data_package_id' => $package->id],
            ['selling_price' => $sellingPrice]
        );

        return response()->json([
            'message' => 'Price saved.',
            'selling_price' => $sellingPrice,
            'your_profit' => round($sellingPrice - $basePrice, 2),
        ]);
    }

    /**
     * Update reseller store setup: store slug (URL part, e.g. iksoft), store name, WhatsApp. Required for store to be accessible.
     */
    public function updateStoreSetup(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isReseller()) {
            return response()->json(['error' => 'You must be an approved reseller.'], 403);
        }

        $data = $request->validate([
            'store_slug' => 'required|string|max:64|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'store_name' => 'required|string|max:120',
            'store_whatsapp' => 'required|string|max:30',
        ], [
            'store_slug.regex' => 'Store URL must be lowercase letters, numbers and hyphens only (e.g. iksoft or my-shop).',
        ]);

        $slug = strtolower(trim($data['store_slug']));
        $exists = \App\Models\User::where('store_slug', $slug)->where('id', '!=', $user->id)->exists();
        if ($exists) {
            throw ValidationException::withMessages(['store_slug' => ['This store URL is already taken. Choose another.']]);
        }

        $user->update([
            'store_slug' => $slug,
            'store_name' => trim($data['store_name']),
            'store_whatsapp' => trim($data['store_whatsapp']),
        ]);

        return response()->json([
            'message' => 'Store setup saved. Your store is live at ' . url('/store/' . $slug),
            'store_slug' => $user->store_slug,
            'store_name' => $user->store_name,
            'store_whatsapp' => $user->store_whatsapp,
            'store_url' => url('/store/' . $user->store_slug),
            'store_setup_complete' => true,
        ]);
    }

    /**
     * Normalize MoMo input to digits; Ghana local 0XXXXXXXXX becomes 233XXXXXXXXX.
     */
    private static function normalizeGhMomoNumber(string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', $raw);
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '233' . substr($digits, 1);
        }
        if (str_starts_with($digits, '233') && strlen($digits) >= 12 && strlen($digits) <= 13) {
            return $digits;
        }

        return null;
    }
}
