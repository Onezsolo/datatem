<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliateWithdrawal;
use App\Models\DataPackage;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\ResellerRequest;
use App\Models\ResellerWithdrawal;
use App\Models\StorefrontPayment;
use App\Models\Topup;
use App\Models\User;
use App\Services\AffiliateService;
use App\Services\Refer2BundleService;
use App\Services\NotifyService;
use App\Services\PaystackService;
use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * Get dashboard statistics (data orders = purchases). All values from database.
     * Amount spent = total revenue from all purchases (what users paid).
     * Profit = sum over data purchases of (amount paid - package console cost); i.e. admin margin when user buys.
     * Total users balance = sum of users.balance (deducted on purchase via decrement).
     */
    public function getDashboardStats()
    {
        $today = now()->startOfDay();

        // Total revenue: sum of amount for all purchases
        $totalSpent = (float) Purchase::sum('amount');
        $todaySpent = (float) Purchase::where('created_at', '>=', $today)->sum('amount');

        // Profit from data sales: for each purchase linked to a package, profit = amount - console_price (admin margin)
        $totalProfit = 0.0;
        $todayProfit = 0.0;
        if (Schema::hasTable('data_packages')) {
            try {
                $totalProfit = (float) Purchase::whereNotNull('data_package_id')
                    ->join('data_packages', 'purchases.data_package_id', '=', 'data_packages.id')
                    ->selectRaw('COALESCE(SUM(purchases.amount - data_packages.console_price), 0) as profit')
                    ->value('profit');
                $todayProfit = (float) Purchase::whereNotNull('data_package_id')
                    ->where('purchases.created_at', '>=', $today)
                    ->join('data_packages', 'purchases.data_package_id', '=', 'data_packages.id')
                    ->selectRaw('COALESCE(SUM(purchases.amount - data_packages.console_price), 0) as profit')
                    ->value('profit');
            } catch (\Throwable $e) {
                // ignore join errors
            }
        }

        $stats = [
            'totalSpent' => $totalSpent,
            'todaySpent' => round($todaySpent, 2),
            'totalUsersBalance' => (float) User::sum('balance'),
            'totalProfit' => round($totalProfit, 2),
            'todayProfit' => round($todayProfit, 2),
            'usersCount' => (int) User::count(),
            'ordersCount' => (int) Purchase::count(),
            'topupsCount' => (int) Topup::count(),
            'paymentsCount' => $this->safeCount('payments'),
            'ticketsCount' => $this->safeCount('tickets'),
            'resellersCount' => (int) ResellerRequest::count(),
            'affiliatesCount' => Schema::hasTable('affiliates') ? (int) Affiliate::count() : 0,
        ];

        return response()->json($stats);
    }

    /**
     * Recent activity for dashboard "Direct User Actions" (recent orders, topups, new users).
     */
    public function getRecentActivity()
    {
        $recentOrders = Purchase::with(['user', 'dataPackage'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function (Purchase $o) {
                return [
                    'id' => $o->id,
                    'type' => 'order',
                    'description' => 'Order #' . $o->id . ' – ' . ($o->data_plan ?? $o->network) . ' for ' . $o->beneficiary,
                    'amount' => (float) $o->amount,
                    'status' => $o->status,
                    'user' => $o->user ? $o->user->full_name : null,
                    'created_at' => $o->created_at?->toIso8601String(),
                ];
            });

        $recentTopups = Topup::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get()
            ->map(function (Topup $t) {
                return [
                    'id' => $t->id,
                    'type' => 'topup',
                    'description' => 'Topup GH₵' . number_format((float) $t->amount, 2) . ' (' . ($t->method ?? 'wallet') . ')',
                    'amount' => (float) $t->amount,
                    'user' => $t->user ? $t->user->full_name : null,
                    'created_at' => $t->created_at?->toIso8601String(),
                ];
            });

        $recentUsers = User::orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function (User $u) {
                return [
                    'id' => $u->id,
                    'type' => 'user',
                    'description' => $u->full_name . ' signed up',
                    'user' => $u->full_name,
                    'created_at' => $u->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'recent_orders' => $recentOrders->values(),
            'recent_topups' => $recentTopups->values(),
            'recent_users' => $recentUsers->values(),
        ]);
    }

    /**
     * Finance statistics with optional date filter. For admin Statistics page.
     * Returns: amount_spent, total_users_balance, profit_earned, manual_payments (success/failed), wallet_topups, total_orders, affiliate_expenses, and daily breakdown.
     */
    public function getFinanceStatistics(Request $request)
    {
        $dateFrom = $request->input('date_from') ? \Carbon\Carbon::parse($request->input('date_from'))->startOfDay() : now()->subDays(30)->startOfDay();
        $dateTo = $request->input('date_to') ? \Carbon\Carbon::parse($request->input('date_to'))->endOfDay() : now()->endOfDay();

        $purchasesQuery = Purchase::whereBetween('created_at', [$dateFrom, $dateTo]);
        $amountSpent = (float) (clone $purchasesQuery)->sum('amount');
        $totalOrders = (int) (clone $purchasesQuery)->count();

        $profitEarned = 0.0;
        if (Schema::hasTable('data_packages')) {
            try {
                $profitEarned = (float) Purchase::whereNotNull('data_package_id')
                    ->whereBetween('purchases.created_at', [$dateFrom, $dateTo])
                    ->join('data_packages', 'purchases.data_package_id', '=', 'data_packages.id')
                    ->selectRaw('COALESCE(SUM(purchases.amount - data_packages.console_price), 0) as profit')
                    ->value('profit');
            } catch (\Throwable $e) {
                //
            }
        }

        $totalUsersBalance = (float) User::sum('balance');

        $paymentsQuery = Payment::whereBetween('created_at', [$dateFrom, $dateTo]);
        $manualPaymentsSuccess = (int) (clone $paymentsQuery)->where('status', 'completed')->count();
        $manualPaymentsSuccessAmount = (float) (clone $paymentsQuery)->where('status', 'completed')->sum('amount');
        $manualPaymentsFailed = (int) Payment::whereBetween('created_at', [$dateFrom, $dateTo])->whereIn('status', ['failed', 'rejected'])->count();
        $manualPaymentsPending = (int) Payment::whereBetween('created_at', [$dateFrom, $dateTo])->where('status', 'pending')->count();

        $topupsQuery = Topup::whereBetween('created_at', [$dateFrom, $dateTo]);
        $walletTopupsCount = (int) (clone $topupsQuery)->count();
        $walletTopupsSum = (float) (clone $topupsQuery)->sum('amount');

        $affiliateExpenses = 0.0;
        if (Schema::hasTable('affiliate_withdrawals')) {
            $affiliateExpenses = (float) AffiliateWithdrawal::where('status', 'approved')
                ->whereBetween('processed_at', [$dateFrom, $dateTo])
                ->sum('amount');
        }

        $dailyBreakdown = Purchase::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders_count, COALESCE(SUM(amount), 0) as amount_spent')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dailyProfit = [];
        if (Schema::hasTable('data_packages')) {
            try {
                $dailyProfit = Purchase::whereNotNull('data_package_id')
                    ->whereBetween('purchases.created_at', [$dateFrom, $dateTo])
                    ->join('data_packages', 'purchases.data_package_id', '=', 'data_packages.id')
                    ->selectRaw('DATE(purchases.created_at) as date, COALESCE(SUM(purchases.amount - data_packages.console_price), 0) as profit')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->keyBy('date');
            } catch (\Throwable $e) {
                //
            }
        }
        foreach ($dailyBreakdown as $row) {
            $d = $row->date instanceof \DateTimeInterface ? $row->date->format('Y-m-d') : (string) $row->date;
            $row->profit = isset($dailyProfit[$d]) ? (float) $dailyProfit[$d]->profit : 0;
        }

        $shopRevenue = 0.0;
        $shopPaidOrders = 0;
        $shopPendingOrders = 0;
        $shopDeliveryFees = 0.0;
        $shopDaily = collect();

        if (Schema::hasTable('shop_orders') && Schema::hasTable('shop_payments')) {
            try {
                $shopRevenue = (float) \App\Shop\Models\Payment::query()
                    ->where('status', 'paid')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->sum('amount');

                $paidOrders = \App\Shop\Models\Order::query()
                    ->where('payment_status', 'paid')
                    ->whereBetween('created_at', [$dateFrom, $dateTo]);

                $shopPaidOrders = (int) (clone $paidOrders)->count();
                $shopDeliveryFees = (float) (clone $paidOrders)->sum('delivery_fee');

                $shopPendingOrders = (int) \App\Shop\Models\Order::query()
                    ->where('payment_status', 'pending')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->count();

                $shopDaily = \App\Shop\Models\Order::query()
                    ->where('payment_status', 'paid')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as orders_count, COALESCE(SUM(total), 0) as amount')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
            } catch (\Throwable $e) {
                // Shop tables unavailable; keep zeroed metrics.
            }
        }

        return response()->json([
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'amount_spent' => round($amountSpent, 2),
            'total_users_balance' => round($totalUsersBalance, 2),
            'profit_earned' => round($profitEarned, 2),
            'manual_payments_success_count' => $manualPaymentsSuccess,
            'manual_payments_success_amount' => round($manualPaymentsSuccessAmount, 2),
            'manual_payments_failed_count' => $manualPaymentsFailed,
            'manual_payments_pending_count' => $manualPaymentsPending,
            'wallet_topups_count' => $walletTopupsCount,
            'wallet_topups_sum' => round($walletTopupsSum, 2),
            'total_orders' => $totalOrders,
            'affiliate_expenses' => round($affiliateExpenses, 2),
            'shop_revenue' => round($shopRevenue, 2),
            'shop_paid_orders' => $shopPaidOrders,
            'shop_pending_orders' => $shopPendingOrders,
            'shop_delivery_fees' => round($shopDeliveryFees, 2),
            'combined_revenue' => round($amountSpent + $shopRevenue, 2),
            'shop_daily_breakdown' => $shopDaily,
            'daily_breakdown' => $dailyBreakdown,
        ]);
    }

    /**
     * Get the last profit-calc snapshot (user balance as at date) so the next calculation
     * uses it as "user balance at start" and does not double-count that amount.
     */
    public function getProfitCalcSnapshot()
    {
        $date = SettingsService::get('profit_calc_snapshot_date', '');
        $balance = SettingsService::get('profit_calc_snapshot_balance', '');

        return response()->json([
            'snapshot_date' => $date ?: null,
            'snapshot_balance' => $balance !== '' ? (float) $balance : null,
        ]);
    }

    /**
     * Save current total user balance as the "start balance" for the next period.
     * Call this after a calculation so next time the 77 (or whatever) is not included again.
     */
    public function saveProfitCalcSnapshot(Request $request)
    {
        $balance = $request->input('balance');
        if ($balance === null || $balance === '') {
            return response()->json(['error' => 'Balance is required.'], 422);
        }
        $balance = (float) $balance;
        $date = now()->toDateString();

        SettingsService::set('profit_calc_snapshot_date', $date);
        SettingsService::set('profit_calc_snapshot_balance', (string) round($balance, 2));

        return response()->json([
            'snapshot_date' => $date,
            'snapshot_balance' => round($balance, 2),
        ]);
    }

    /** Return table count or 0 if table/model missing. */
    private function safeCount(string $table): int
    {
        try {
            if ($table === 'payments' && Schema::hasTable('payments')) {
                return (int) DB::table('payments')->count();
            }
            if ($table === 'tickets' && Schema::hasTable('tickets')) {
                return (int) DB::table('tickets')->count();
            }
        } catch (\Throwable $e) {
            //
        }
        return 0;
    }

    /**
     * Get data orders (purchases) with pagination. Expose package (data_plan) and number (beneficiary) for admin UI.
     * Query params: search (id, beneficiary, customer name), status, sort_by (id|amount|profit|created_at), sort_dir (asc|desc), export=1.
     */
    public function getOrders(Request $request)
    {
        $query = Purchase::with(['user', 'reseller', 'dataPackage']);

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('purchases.id', 'like', '%' . $search . '%')
                    ->orWhere('purchases.beneficiary', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('full_name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('reseller', function ($r) use ($search) {
                        $r->where('full_name', 'like', '%' . $search . '%')
                            ->orWhere('store_name', 'like', '%' . $search . '%');
                    });
            });
        }
        $status = $request->input('status');
        if ($status && in_array($status, ['delivered', 'processing', 'pending', 'failed'], true)) {
            $query->where('purchases.status', $status);
        }
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        if ($sortBy === 'profit') {
            $query->leftJoin('data_packages as dp_ord', 'purchases.data_package_id', '=', 'dp_ord.id')
                ->orderByRaw('(purchases.amount - COALESCE(dp_ord.console_price, 0)) ' . ($sortDir === 'asc' ? 'ASC' : 'DESC'))
                ->select('purchases.*');
        } elseif (in_array($sortBy, ['id', 'amount', 'created_at'], true)) {
            $query->orderBy('purchases.' . $sortBy, $sortDir);
        } else {
            $query->orderBy('purchases.created_at', 'desc');
        }

        $addOrderFields = function (Purchase $o) {
            $cost = $o->dataPackage ? (float) $o->dataPackage->console_price : 0;
            $amount = (float) $o->amount;
            return ['profit' => round($amount - $cost, 2)];
        };
        $mapOrder = function (Purchase $o) use ($addOrderFields) {
            $arr = $o->toArray();
            $arr['package'] = $o->data_plan ?? trim(($o->network ?? '') . ' ' . ($o->data_plan ?? ''));
            $arr['number'] = $o->beneficiary;
            $arr['customer'] = $o->user_id ? ($o->user ? $o->user->full_name : '—') : ('Walking customer' . ($o->reseller ? ' (Reseller: ' . $o->reseller->full_name . ')' : ''));
            $arr['profit'] = $addOrderFields($o)['profit'];
            $arr['source'] = $o->reseller_id ? 'storefront' : 'site';
            $arr['source_label'] = $o->reseller_id && $o->reseller
                ? (trim((string) $o->reseller->store_name) ?: $o->reseller->full_name)
                : null;
            return $arr;
        };

        if ($request->boolean('export')) {
            $collection = $query->get()->map($mapOrder);
            return response()->json(['data' => $collection->values(), 'total' => $collection->count()]);
        }
        $perPage = max(5, min(50, (int) $request->input('per_page', 20)));
        $paginator = $query->paginate($perPage);
        $paginator->getCollection()->transform($mapOrder);
        return response()->json($paginator);
    }

    /**
     * Resend a pending order to the API (JayBart). Only orders with status "pending" can be resent.
     */
    public function resendOrder($id)
    {
        $purchase = Purchase::with('dataPackage')->find($id);
        if (! $purchase) {
            return response()->json(['error' => 'Order not found.'], 404);
        }
        if ($purchase->status !== 'pending') {
            return response()->json(['error' => 'Only pending orders can be resent to the API.'], 400);
        }

        $package = $purchase->dataPackage;
        if (! $package) {
            return response()->json(['error' => 'Order has no linked package. Cannot resend.'], 400);
        }

        $networkId = (int) ($package->network_id ?? 0);
        $volume = (float) $package->volume;
        $sharedBundle = $volume > 0 ? (int) round($volume * 1000) : 0;
        if ($networkId < 1 || $sharedBundle < 1) {
            return response()->json(['error' => 'Package network or volume not set. Sync packages in Admin and try again.'], 400);
        }

        $beneficiary = preg_replace('/\s+/', '', (string) $purchase->beneficiary);
        $dataPlanOverride = $package->volume_gb ? trim((string) $package->volume_gb) : null;
        $providerBundleId = $package->provider_bundle_id ? trim((string) $package->provider_bundle_id) : null;
        $networkNameOverride = $package->network ? trim((string) $package->network) : null;
        // If this order was linked to the wrong MTN 1 GB (54.50) or Express(MTN) 1 GB (64.50), use the correct MTN 1 GB so the API charges ~4.50 and we send network "MTN".
        if (DataPackage::shouldUseCorrectMtn1GbForApi($package)) {
            $correctMtn1Gb = DataPackage::getCorrectMtn1GbPackage();
            if ($correctMtn1Gb) {
                $dataPlanOverride = $correctMtn1Gb->volume_gb ? trim((string) $correctMtn1Gb->volume_gb) : '1 GB';
                $providerBundleId = $correctMtn1Gb->provider_bundle_id ? trim((string) $correctMtn1Gb->provider_bundle_id) : null;
                $networkNameOverride = $correctMtn1Gb->network ? trim((string) $correctMtn1Gb->network) : 'MTN';
                $networkId = (int) ($correctMtn1Gb->network_id ?? $networkId);
                $sharedBundle = $correctMtn1Gb->volume > 0 ? (int) round((float) $correctMtn1Gb->volume * 1000) : $sharedBundle;
            }
        }
        $apiResult = Refer2BundleService::buyPackage($beneficiary, $networkId, $sharedBundle, $dataPlanOverride, $providerBundleId, $networkNameOverride);

        if ($apiResult['success']) {
            $transactionCode = $apiResult['transaction_code'] ?? null;
            $purchase->update([
                'status' => 'processing',
                'transaction_id' => $transactionCode,
                'failure_reason' => null,
            ]);
            return response()->json([
                'message' => 'Order sent to API successfully.',
                'order' => $purchase->fresh(['user', 'dataPackage']),
            ]);
        }

        $message = $apiResult['message'] ?? 'API call failed.';
        $purchase->update(['failure_reason' => $message]);
        return response()->json(['error' => $message], 422);
    }

    /**
     * Check order status from the API and update the purchase (e.g. processing → delivered/failed).
     */
    public function checkOrderStatus($id)
    {
        $purchase = Purchase::with('user')->find($id);
        if (! $purchase) {
            return response()->json(['error' => 'Order not found.'], 404);
        }

        $tid = trim((string) ($purchase->transaction_id ?? ''));
        if ($tid === '') {
            return response()->json(['error' => 'Order has no transaction ID. Resend to API first.'], 400);
        }

        $result = Refer2BundleService::fetchTransactionStatus($tid);
        if (! ($result['success'] ?? false)) {
            return response()->json([
                'error' => $result['message'] ?? 'Failed to fetch status from API.',
            ], 422);
        }

        $orderItems = $result['order_items'] ?? [];
        if (count($orderItems) === 0) {
            return response()->json(['error' => 'API returned no order items.'], 422);
        }

        $beneficiaryNorm = preg_replace('/\s+/', '', (string) $purchase->beneficiary);
        $item = collect($orderItems)->first(function ($i) use ($beneficiaryNorm) {
            $num = preg_replace('/\s+/', '', (string) ($i['beneficiary_number'] ?? ''));
            return $num === $beneficiaryNorm;
        }) ?? $orderItems[0];

        $apiStatus = (string) ($item['status'] ?? '');
        $mappedStatus = Refer2BundleService::mapApiStatusToOurStatus($apiStatus);

        $purchase->update(['status' => $mappedStatus]);

        if (in_array($mappedStatus, ['delivered', 'failed'], true) && $purchase->user) {
            NotifyService::notifyUserPurchaseStatus($purchase->fresh());
        }
        if ($mappedStatus === 'delivered') {
            AffiliateService::creditCommissionForPurchase($purchase->fresh());
            \App\Services\ResellerService::creditProfitForPurchase($purchase->fresh());
        }

        $updated = $purchase->fresh(['user', 'dataPackage']);
        $arr = $updated->toArray();
        $arr['package'] = $updated->data_plan ?? trim(($updated->network ?? '') . ' ' . ($updated->data_plan ?? ''));
        $arr['number'] = $updated->beneficiary;

        return response()->json([
            'message' => 'Status updated from API.',
            'status' => $mappedStatus,
            'order' => $arr,
        ]);
    }

    /**
     * Check status for all orders that have a transaction_id (calls external API for each). Limited per run to avoid timeout.
     */
    public function checkAllOrdersStatus(Request $request)
    {
        $limit = max(1, min(100, (int) $request->input('limit', 50)));
        $purchases = Purchase::where('status', 'processing')
            ->whereNotNull('transaction_id')
            ->where('transaction_id', '!=', '')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $checked = 0;
        $updated = 0;
        $errors = [];

        foreach ($purchases as $purchase) {
            $tid = trim((string) $purchase->transaction_id);
            if ($tid === '') {
                continue;
            }
            $checked++;
            $result = Refer2BundleService::fetchTransactionStatus($tid);
            if (! ($result['success'] ?? false)) {
                $errors[] = 'Order #' . $purchase->id . ': ' . ($result['message'] ?? 'Failed to fetch status');
                continue;
            }
            $orderItems = $result['order_items'] ?? [];
            if (count($orderItems) === 0) {
                $errors[] = 'Order #' . $purchase->id . ': No order items from API';
                continue;
            }
            $beneficiaryNorm = preg_replace('/\s+/', '', (string) $purchase->beneficiary);
            $item = collect($orderItems)->first(function ($i) use ($beneficiaryNorm) {
                $num = preg_replace('/\s+/', '', (string) ($i['beneficiary_number'] ?? ''));

                return $num === $beneficiaryNorm;
            }) ?? $orderItems[0];
            $apiStatus = (string) ($item['status'] ?? '');
            $mappedStatus = Refer2BundleService::mapApiStatusToOurStatus($apiStatus);
            $previousStatus = $purchase->status;
            $purchase->update(['status' => $mappedStatus]);
            if ($previousStatus !== $mappedStatus) {
                $updated++;
            }
            if (in_array($mappedStatus, ['delivered', 'failed'], true) && $purchase->user) {
                NotifyService::notifyUserPurchaseStatus($purchase->fresh());
            }
            if ($mappedStatus === 'delivered') {
                AffiliateService::creditCommissionForPurchase($purchase->fresh());
                \App\Services\ResellerService::creditProfitForPurchase($purchase->fresh());
            }
        }

        return response()->json([
            'message' => "Checked {$checked} order(s). Status updated for {$updated}.",
            'checked' => $checked,
            'updated' => $updated,
            'errors' => array_slice($errors, 0, 10),
        ]);
    }

    /**
     * Get topups plus pending Paystack payments (for admin Topups page), paginated.
     * Returns combined list with record_type 'topup' or 'paystack_pending'.
     */
    public function getTopups(Request $request)
    {
        $topups = Topup::with('user')->orderBy('created_at', 'desc')->get()->map(function (Topup $t) {
            $arr = $t->toArray();
            $arr['record_type'] = 'topup';
            $arr['reference'] = $t->id;
            return $arr;
        });

        $pendingPaystack = Payment::with('user')->where('status', 'pending')->orderBy('created_at', 'desc')->get()->map(function (Payment $p) {
            return [
                'id' => $p->id,
                'reference' => $p->reference,
                'user_id' => $p->user_id,
                'user' => $p->user,
                'amount' => $p->amount,
                'method' => 'paystack',
                'status' => 'pending',
                'created_at' => $p->created_at?->toIso8601String(),
                'record_type' => 'paystack_pending',
                'source' => null,
                'reseller' => null,
            ];
        });

        $pendingStorefront = StorefrontPayment::with('reseller')->where('status', 'pending')->orderBy('created_at', 'desc')->get()->map(function (StorefrontPayment $p) {
            $reseller = $p->reseller;
            return [
                'id' => $p->id,
                'reference' => $p->id,
                'user_id' => null,
                'user' => null,
                'amount' => $p->amount,
                'method' => 'paystack',
                'status' => 'pending',
                'created_at' => $p->created_at?->toIso8601String(),
                'record_type' => 'storefront_pending',
                'source' => 'storefront',
                'reseller' => $reseller ? ['id' => $reseller->id, 'full_name' => $reseller->full_name, 'store_name' => $reseller->store_name, 'store_slug' => $reseller->store_slug] : null,
            ];
        });

        $completedStorefront = StorefrontPayment::with('reseller')->where('status', 'completed')->orderBy('created_at', 'desc')->get()->map(function (StorefrontPayment $p) {
            $reseller = $p->reseller;
            return [
                'id' => $p->id,
                'reference' => $p->id,
                'user_id' => null,
                'user' => null,
                'amount' => $p->amount,
                'method' => 'paystack',
                'status' => 'completed',
                'created_at' => $p->created_at?->toIso8601String(),
                'record_type' => 'storefront_completed',
                'source' => 'storefront',
                'reseller' => $reseller ? ['id' => $reseller->id, 'full_name' => $reseller->full_name, 'store_name' => $reseller->store_name, 'store_slug' => $reseller->store_slug] : null,
            ];
        });

        $combined = $topups->concat($pendingPaystack->values())->concat($pendingStorefront->values())->concat($completedStorefront->values());

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $searchLower = mb_strtolower($search);
            $combined = $combined->filter(function ($item) use ($searchLower) {
                $id = (string) ($item['id'] ?? '');
                $ref = (string) ($item['reference'] ?? '');
                $user = $item['user'] ?? null;
                $reseller = $item['reseller'] ?? null;
                $customer = $user ? (($user->full_name ?? '') . ' ' . ($user->email ?? '') . ' ' . ($user->phone ?? '')) : ($reseller ? (($reseller['full_name'] ?? '') . ' ' . ($reseller['store_name'] ?? '')) : '');
                return str_contains(mb_strtolower($id), $searchLower)
                    || str_contains(mb_strtolower($ref), $searchLower)
                    || str_contains(mb_strtolower($customer), $searchLower);
            })->values();
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $combined = $combined->sort(function ($a, $b) use ($sortBy, $sortDir) {
            $va = $a[$sortBy] ?? ($a['created_at'] ?? '');
            $vb = $b[$sortBy] ?? ($b['created_at'] ?? '');
            if ($sortBy === 'amount') {
                $va = (float) $va;
                $vb = (float) $vb;
            } elseif ($sortBy === 'created_at' || $sortBy === 'id') {
                $va = is_string($va) ? $va : ($va instanceof \Carbon\Carbon ? $va->format('c') : '');
                $vb = is_string($vb) ? $vb : ($vb instanceof \Carbon\Carbon ? $vb->format('c') : '');
            }
            $cmp = $va <=> $vb;
            return $sortDir === 'asc' ? $cmp : -$cmp;
        })->values();

        if ($request->boolean('export')) {
            return response()->json(['data' => $combined->values(), 'total' => $combined->count()]);
        }
        $perPage = max(5, min(50, (int) $request->input('per_page', 20)));
        $page = max(1, (int) $request->input('page', 1));
        $total = $combined->count();
        $lastPage = (int) ceil($total / $perPage) ?: 1;
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;
        $items = $combined->slice($offset, $perPage)->values();
        return response()->json([
            'data' => $items,
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }

    /**
     * Verify a Paystack payment by reference and credit user if successful.
     * Used from admin Topups page "Check status" for pending Paystack payments.
     */
    public function verifyPaystackPayment($reference)
    {
        $payment = Payment::with('user')->where('id', $reference)->first();
        if (! $payment) {
            return response()->json(['error' => 'Payment not found.'], 404);
        }
        if ($payment->status !== 'pending') {
            return response()->json(['error' => 'Payment already processed.'], 400);
        }

        $paystack = new PaystackService();
        $verify = $paystack->verifyTransaction($reference);

        if (! ($verify['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'verified' => false,
                'message' => $verify['message'] ?? 'Verification failed.',
            ], 400);
        }
        if (! ($verify['verified'] ?? false)) {
            return response()->json([
                'success' => false,
                'verified' => false,
                'message' => 'Payment not successful on Paystack (status: ' . ($verify['status'] ?? 'unknown') . ').',
            ], 400);
        }

        // Security: only credit if amount actually paid on Paystack matches what we requested (amount in GHS)
        $amountPaid = round((float) ($verify['amount'] ?? 0), 2);
        $expectedCharged = $payment->amount_charged !== null && $payment->amount_charged !== ''
            ? round((float) $payment->amount_charged, 2)
            : round((float) $payment->amount + round((float) $payment->amount * 0.03, 2), 2);
        if (abs($amountPaid - $expectedCharged) > 0.02) {
            Log::warning('Admin verify Paystack amount mismatch', [
                'reference' => $reference,
                'amount_paid' => $amountPaid,
                'expected_charged' => $expectedCharged,
                'payment_amount' => (float) $payment->amount,
                'payment_amount_charged' => $payment->amount_charged,
            ]);
            return response()->json([
                'success' => false,
                'verified' => false,
                'message' => 'Payment amount mismatch. Amount received (' . $amountPaid . ') does not match expected (' . $expectedCharged . '). Ref: ' . $reference,
            ], 400);
        }

        $user = $payment->user;
        if (! $user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        $amount = (float) $payment->amount;
        $payment->status = 'completed';
        $payment->save();

        $user->balance = (float) $user->balance + $amount;
        $user->save();

        $topup = Topup::create([
            'id' => 'TUP-' . strtoupper(Str::random(12)),
            'user_id' => $user->id,
            'amount' => $amount,
            'method' => 'paystack',
            'status' => 'approved',
        ]);

        NotifyService::notifyUserTopupCompleted($user, $topup);
        NotifyService::admins(
            'Wallet topup (Paystack verified by admin)',
            $user->name . ' topped up GH₵ ' . number_format($amount, 2) . ' via Paystack. Ref: ' . $topup->id . '.',
            'info',
            url('/admin/topups')
        );

        return response()->json([
            'success' => true,
            'verified' => true,
            'message' => 'Payment verified and user credited.',
            'topup' => $topup->fresh('user'),
        ]);
    }

    /**
     * Get manual payment submissions (and Paystack) with pagination.
     * Query params: search (id, reference, user name), sort_by, sort_dir, export=1.
     */
    public function getPayments(Request $request)
    {
        $query = Payment::with('user');
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('payments.id', 'like', '%' . $search . '%')
                    ->orWhere('payments.reference', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('full_name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (in_array($sortBy, ['id', 'reference', 'amount', 'status', 'created_at'], true)) {
            $query->orderBy('payments.' . $sortBy, $sortDir);
        } else {
            $query->orderBy('payments.created_at', 'desc');
        }
        if ($request->boolean('export')) {
            $data = $query->get();
            return response()->json(['data' => $data, 'total' => $data->count()]);
        }
        $perPage = max(5, min(50, (int) $request->input('per_page', 20)));
        return response()->json($query->paginate($perPage));
    }

    /**
     * Get tickets with pagination. Query params: search (id, subject, customer), sort_by, sort_dir, export=1.
     */
    public function getTickets(Request $request)
    {
        $query = \App\Models\Ticket::with('user');
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('tickets.id', 'like', '%' . $search . '%')
                    ->orWhere('tickets.subject', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('full_name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
            });
        }
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (in_array($sortBy, ['id', 'subject', 'status', 'created_at'], true)) {
            $query->orderBy('tickets.' . $sortBy, $sortDir);
        } else {
            $query->orderBy('tickets.created_at', 'desc');
        }
        if ($request->boolean('export')) {
            $data = $query->get();
            return response()->json(['data' => $data, 'total' => $data->count()]);
        }
        $perPage = max(5, min(50, (int) $request->input('per_page', 20)));
        return response()->json($query->paginate($perPage));
    }

    /**
     * Get reseller requests (for admin) with pagination. Query params: search (user name/email), status, sort_by, sort_dir, export=1.
     */
    public function getResellers(Request $request)
    {
        $query = ResellerRequest::with(['user', 'reviewer']);
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('full_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }
        $status = $request->input('status');
        if ($status && in_array($status, [ResellerRequest::STATUS_PENDING, ResellerRequest::STATUS_APPROVED, ResellerRequest::STATUS_REJECTED], true)) {
            $query->where('status', $status);
        }
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (in_array($sortBy, ['id', 'status', 'created_at'], true)) {
            $query->orderBy('reseller_requests.' . $sortBy, $sortDir);
        } else {
            $query->orderBy('reseller_requests.created_at', 'desc');
        }
        if ($request->boolean('export')) {
            $requests = $query->get();
            $userIds = $requests->pluck('user_id')->unique()->filter()->values();
            $stats = Purchase::whereIn('reseller_id', $userIds)
                ->selectRaw('reseller_id, COALESCE(SUM(amount), 0) as total_sales, COALESCE(SUM(reseller_profit), 0) as total_profit')
                ->groupBy('reseller_id')
                ->get()
                ->keyBy('reseller_id');
            $requests->transform(function ($req) use ($stats) {
                $s = $stats->get($req->user_id);
                $req->total_sales = $s ? (float) $s->total_sales : 0;
                $req->total_profit = $s ? (float) $s->total_profit : 0;
                return $req;
            });
            return response()->json(['data' => $requests, 'total' => $requests->count()]);
        }
        $perPage = max(5, min(50, (int) $request->input('per_page', 20)));
        $requests = $query->paginate($perPage);
        $userIds = $requests->getCollection()->pluck('user_id')->unique()->filter()->values();
        $stats = Purchase::whereIn('reseller_id', $userIds)
            ->selectRaw('reseller_id, COALESCE(SUM(amount), 0) as total_sales, COALESCE(SUM(reseller_profit), 0) as total_profit')
            ->groupBy('reseller_id')
            ->get()
            ->keyBy('reseller_id');
        $requests->getCollection()->transform(function ($req) use ($stats) {
            $s = $stats->get($req->user_id);
            $req->total_sales = $s ? (float) $s->total_sales : 0;
            $req->total_profit = $s ? (float) $s->total_profit : 0;
            return $req;
        });
        return response()->json($requests);
    }

    /**
     * Approve a reseller request.
     */
    public function approveReseller($id, Request $request)
    {
        $reseller = ResellerRequest::with('user')->find($id);
        if (! $reseller) {
            return response()->json(['error' => 'Reseller request not found.'], 404);
        }
        if ($reseller->status !== ResellerRequest::STATUS_PENDING) {
            return response()->json(['error' => 'Request is no longer pending.'], 400);
        }

        $notes = $request->input('admin_notes', '');
        $reseller->update([
            'status' => ResellerRequest::STATUS_APPROVED,
            'admin_notes' => $notes,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        $user = $reseller->user;
        if ($user && empty($user->reseller_slug)) {
            $user->update(['reseller_slug' => 'r' . $user->id]);
        }
        if ($user) {
            \App\Services\NotifyService::notifyUserResellerApproved($user);
        }

        return response()->json([
            'message' => 'Reseller request approved.',
            'request' => $reseller->fresh(['user', 'reviewer']),
        ]);
    }

    /**
     * Reject a reseller request.
     */
    public function rejectReseller($id, Request $request)
    {
        $reseller = ResellerRequest::with('user')->find($id);
        if (! $reseller) {
            return response()->json(['error' => 'Reseller request not found.'], 404);
        }
        if ($reseller->status !== ResellerRequest::STATUS_PENDING) {
            return response()->json(['error' => 'Request is no longer pending.'], 400);
        }

        $notes = $request->input('admin_notes', '');
        $reseller->update([
            'status' => ResellerRequest::STATUS_REJECTED,
            'admin_notes' => $notes,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Reseller request rejected.',
            'request' => $reseller->fresh(['user', 'reviewer']),
        ]);
    }

    /**
     * Display a listing of the users with pagination.
     * Query params: search (name, email, phone), role, sort_by (id|full_name|email|created_at), sort_dir, export=1.
     */
    public function getUsers(Request $request)
    {
        $query = User::query();
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }
        $role = $request->input('role');
        if ($role && in_array($role, ['admin', 'user'], true)) {
            $query->where('role', $role);
        }
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (in_array($sortBy, ['id', 'full_name', 'email', 'created_at'], true)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        if ($request->boolean('export')) {
            $data = $query->get();
            return response()->json(['data' => $data, 'total' => $data->count()]);
        }
        $perPage = max(5, min(50, (int) $request->input('per_page', 20)));
        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created user in storage.
     */
    public function storeUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:admin,user',
            'balance' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'balance' => $request->balance,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }

    /**
     * Update the specified user in storage.
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'required|string|max:20|unique:users,phone,' . $user->id,
            'role' => 'required|string|in:admin,user',
            'password' => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user->full_name = $request->full_name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->role = $request->role;

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Remove the specified user from storage.
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent self-deletion
        if (auth('api')->id() == $user->id) {
            return response()->json(['error' => 'You cannot delete your own account'], 400);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * List data packages (for admin; used for sync UI and profit editing).
     */
    public function getPackages()
    {
        $packages = DataPackage::orderBy('network')->orderBy('volume')->excludeExpressNetworks()->excludeProviderBundleIds()->get();
        $networkOrder = ['MTN' => 0, 'AT' => 1, 'AirtelTigo' => 1, 'Telecel' => 2];
        $packages = $packages->sort(function ($a, $b) use ($networkOrder) {
            $na = $a->network ?? '';
            $nb = $b->network ?? '';
            $ia = 3;
            $ib = 3;
            foreach ($networkOrder as $net => $i) {
                if (stripos($na, $net) !== false) { $ia = $i; break; }
            }
            foreach ($networkOrder as $net => $i) {
                if (stripos($nb, $net) !== false) { $ib = $i; break; }
            }
            return $ia <=> $ib ?: strcasecmp($na, $nb) ?: 0;
        })->values();
        return response()->json($packages->map(function (DataPackage $p) {
            return [
                'id' => $p->id,
                'external_id' => $p->external_id,
                'network_id' => $p->network_id,
                'network' => $p->network,
                'volume' => (float) $p->volume,
                'volume_gb' => $p->volume_gb,
                'provider_bundle_id' => $p->provider_bundle_id,
                'console_price' => (float) $p->console_price,
                'selling_price' => $p->selling_price !== null ? (float) $p->selling_price : null,
                'walking_customer_price' => $p->walking_customer_price !== null ? (float) $p->walking_customer_price : null,
                'status' => $p->status,
                'display_price' => $p->display_price,
                'plan_label' => $p->plan_label,
            ];
        }));
    }

    /**
     * Sync data packages from Refer2Bundle API to database. Preserves existing selling_price.
     * Uses Refer2Bundle API key from Admin → Settings or REFER2BUNDLE_API_KEY in .env.
     */
    public function syncPackages()
    {
        $apiKey = Refer2BundleService::getApiKey();
        if ($apiKey === '') {
            return response()->json([
                'message' => 'Refer2Bundle API key is not set. Add it in Admin → Settings or in .env as REFER2BUNDLE_API_KEY.',
                'error' => 'api_key_not_set',
            ], 400);
        }

        $result = Refer2BundleService::syncPackagesToDb();

        if (!empty($result['error'])) {
            $status = ($result['error'] === 'api_key_not_set') ? 400 : 502;
            return response()->json([
                'message' => $result['message'] ?? 'Sync failed. Check API key and network.',
                'error' => $result['error'],
            ], $status);
        }

        return response()->json([
            'message' => 'Packages synced.',
            'created' => $result['created'],
            'updated' => $result['updated'],
            'total' => $result['total'],
        ]);
    }

    /**
     * Update package selling price (admin profit). Optional walking_customer_price.
     */
    public function updatePackage(Request $request, $id)
    {
        $data = $request->validate([
            'selling_price' => 'required|numeric|min:0',
            'walking_customer_price' => 'nullable|numeric|min:0',
        ]);

        $package = DataPackage::findOrFail($id);
        $package->selling_price = $data['selling_price'];
        $package->walking_customer_price = isset($data['walking_customer_price']) && $data['walking_customer_price'] !== ''
            ? $data['walking_customer_price']
            : null;
        $package->save();

        return response()->json([
            'message' => 'Package price updated.',
            'package' => [
                'id' => $package->id,
                'selling_price' => (float) $package->selling_price,
                'display_price' => $package->display_price,
                'plan_label' => $package->plan_label,
            ],
        ]);
    }

    /**
     * Adjust user balance. When type is topup, creates a Topup record and notifies user (in-app + SMS).
     */
    public function adjustBalance(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:topup,deduct',
            'reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $amount = (float) abs($request->amount);
        if ($amount < 0.01) {
            return response()->json(['error' => 'Amount must be at least 0.01'], 422);
        }

        $currentBalance = (float) $user->balance;

        if ($request->type === 'deduct') {
            if ($currentBalance < $amount) {
                return response()->json(['error' => 'Insufficient balance to deduct. Current: GH₵ ' . number_format($currentBalance, 2)], 400);
            }
        }

        DB::transaction(function () use ($user, $amount, $request) {
            if ($request->type === 'deduct') {
                if (Schema::hasColumn('users', 'reseller_earnings_balance')) {
                    $fromReseller = min((float) $user->reseller_earnings_balance, $amount);
                    if ($fromReseller > 0) {
                        $user->decrement('reseller_earnings_balance', $fromReseller);
                    }
                }
                $user->balance = (float) $user->balance - $amount;
            } else {
                $user->balance = (float) $user->balance + $amount;
                $topup = Topup::create([
                    'id' => 'TUP-' . strtoupper(Str::random(12)),
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'method' => 'manual',
                    'status' => 'approved',
                ]);
                NotifyService::notifyUserTopupCompleted($user, $topup);
                NotifyService::admins(
                    'Wallet topup (manual)',
                    $user->name . ' was credited GH₵ ' . number_format($amount, 2) . ' via balance adjustment. Ref: ' . $topup->id . '.',
                    'info',
                    url('/admin/topups')
                );
            }
            $user->save();
        });

        $user->refresh();

        return response()->json([
            'message' => 'Balance adjusted successfully',
            'user' => $user->only(['id', 'full_name', 'email', 'balance', 'role']),
        ]);
    }

    /**
     * Approve a pending topup: credit user balance, update status, notify user (in-app + SMS).
     */
    public function approveTopup($id)
    {
        $topup = Topup::with('user')->findOrFail($id);
        if ($topup->status !== 'pending') {
            return response()->json(['error' => 'Topup already processed.'], 400);
        }

        $user = $topup->user;
        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        $user->balance = (float) $user->balance + (float) $topup->amount;
        $user->save();
        $topup->status = 'approved';
        $topup->save();

        NotifyService::notifyUserTopupCompleted($user, $topup);
        NotifyService::admins(
            'Wallet topup approved',
            $user->name . ' topped up GH₵ ' . number_format((float) $topup->amount, 2) . '. Ref: ' . $topup->id . '.',
            'info',
            url('/admin/topups')
        );

        return response()->json([
            'message' => 'Topup approved. User notified.',
            'topup' => $topup->fresh('user'),
        ]);
    }

    /**
     * Get system settings for admin (from database via SettingsService).
     */
    public function getSettings()
    {
        $all = SettingsService::all();
        return response()->json([
            'site_name' => $all['site_name'] ?? config('app.name', 'R2BMarket'),
            'site_description' => $all['site_description'] ?? 'R2BMarket – Affordable mobile data bundles for MTN, AirtelTigo & Telecel in Ghana.',
            'site_logo_url' => SettingsService::getLogoUrl(),
            'site_favicon_url' => SettingsService::getFaviconUrl(),
            'theme_primary_hex' => $all['theme_primary_hex'] ?? '#9333ea',
            'theme_gradient_start' => $all['theme_gradient_start'] ?? '#a855f7',
            'theme_gradient_end' => $all['theme_gradient_end'] ?? '#6b21a8',
            'maintenance_mode' => ($all['maintenance_mode'] ?? '0') === '1',
            'otp_verification_enabled' => (isset($all['otp_verification_enabled']) && ($all['otp_verification_enabled'] === '1' || $all['otp_verification_enabled'] === 'true')),
            'affiliate_system_enabled' => (isset($all['affiliate_system_enabled']) && ($all['affiliate_system_enabled'] === '1' || $all['affiliate_system_enabled'] === 'true')),
            'min_withdraw' => $all['min_withdraw'] ?? '50.00',
            'txn_fee' => $all['txn_fee'] ?? '1.5',
            'referral_bonus' => $all['referral_bonus'] ?? '2.00',
            'affiliate_signup_bonus' => $all['affiliate_signup_bonus'] ?? '1.00',
            'affiliate_commission' => $all['affiliate_commission'] ?? '2.00',
            'admin_whatsapp' => $all['contact_phone'] ?? '',
            'whatsapp_channel' => $all['support_whatsapp_group_url'] ?? '',
            'support_email' => $all['contact_email'] ?? '',
            'manual_payment_number' => $all['manual_payment_mobile_money'] ?? '0241234567',
            'manual_payment_name' => $all['manual_payment_account_name'] ?? 'R2BMarket Official',
            'manual_topup_enabled' => (isset($all['manual_topup_enabled']) && ($all['manual_topup_enabled'] === '1' || $all['manual_topup_enabled'] === 'true')),
            'platform_balance' => $all['platform_balance'] ?? '0',
            'smtp_host' => $all['smtp_host'] ?? '',
            'smtp_port' => $all['smtp_port'] ?? '587',
            'smtp_encryption' => $all['smtp_encryption'] ?? 'tls',
            'smtp_username' => $all['smtp_username'] ?? '',
            'smtp_password' => '', // never expose stored password
            'smtp_from_address' => $all['smtp_from_address'] ?? '',
            'smtp_from_name' => $all['smtp_from_name'] ?? config('app.name', 'R2BMarket'),
            'admin_notification_email' => $all['admin_notification_email'] ?? 'r2bmarketservices@gmail.com',
            // Integrations: never expose secret keys; only _set booleans and non-secret values
            'paystack_public_key' => $all['paystack_public_key'] ?? config('services.paystack.public_key', ''),
            'paystack_secret_key_set' => trim((string) ($all['paystack_secret_key'] ?? config('services.paystack.secret_key', ''))) !== '',
            'sms_sender_id' => $all['sms_sender_id'] ?? config('services.sms.sender_id', ''),
            'sms_api_key_set' => trim((string) ($all['sms_api_key'] ?? config('services.sms.api_key', ''))) !== '',
            'refer2bundle_base_url' => $all['refer2bundle_base_url'] ?? config('services.refer2bundle.base_url', ''),
            'refer2bundle_api_key_set' => trim((string) ($all['refer2bundle_api_key'] ?? config('services.refer2bundle.api_key', ''))) !== '',
        ]);
    }

    /**
     * Update system settings (save to database via SettingsService).
     * Secret keys are only updated when a non-empty value is sent; never logged.
     */
    public function updateSettings(Request $request)
    {
        $rules = [
            'site_name' => 'nullable|string|max:255',
            'site_description' => 'nullable|string|max:500',
            'theme_primary_hex' => 'nullable|string|max:20',
            'theme_gradient_start' => 'nullable|string|max:20',
            'theme_gradient_end' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,gif,webp,svg|max:2048',
            'favicon' => 'nullable|file|mimes:png,jpeg,jpg,gif,ico|max:512',
            'maintenance_mode' => 'nullable|boolean',
            'otp_verification_enabled' => 'nullable|boolean',
            'affiliate_system_enabled' => 'nullable|boolean',
            'min_withdraw' => 'nullable|string|max:50',
            'txn_fee' => 'nullable|string|max:50',
            'referral_bonus' => 'nullable|string|max:50',
            'affiliate_signup_bonus' => 'nullable|string|max:50',
            'affiliate_commission' => 'nullable|string|max:50',
            'admin_whatsapp' => 'nullable|string|max:50',
            'whatsapp_channel' => 'nullable|string|max:500',
            'support_email' => 'nullable|email|max:255',
            'manual_payment_number' => 'nullable|string|max:50',
            'manual_payment_name' => 'nullable|string|max:255',
            'manual_topup_enabled' => 'nullable|boolean',
            'platform_balance' => 'nullable|numeric|min:0',
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|string|max:10',
            'smtp_encryption' => 'nullable|string|max:10',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:500',
            'smtp_from_address' => 'nullable|email|max:255',
            'smtp_from_name' => 'nullable|string|max:255',
            'admin_notification_email' => 'nullable|email|max:255',
            'paystack_public_key' => 'nullable|string|max:255',
            'paystack_secret_key' => 'nullable|string|max:500',
            'sms_api_key' => 'nullable|string|max:500',
            'sms_sender_id' => 'nullable|string|max:100',
            'refer2bundle_base_url' => 'nullable|string|max:500',
            'refer2bundle_api_key' => 'nullable|string|max:500',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $maintenance = $request->boolean('maintenance_mode');
        $adminWhatsapp = $request->input('admin_whatsapp');
        $whatsappChatUrl = $adminWhatsapp && str_starts_with((string) $adminWhatsapp, 'http')
            ? $adminWhatsapp
            : ($adminWhatsapp ? 'https://wa.me/' . preg_replace('/\D/', '', (string) $adminWhatsapp) : '');
        $manualTopupEnabled = $request->boolean('manual_topup_enabled');

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('settings', 'public');
            SettingsService::set('site_logo', $path);
        }
        if ($request->hasFile('favicon')) {
            $path = $request->file('favicon')->store('settings', 'public');
            SettingsService::set('site_favicon', $path);
        }

        $themePrimary = self::normalizeHexInput($request->input('theme_primary_hex'));
        if ($themePrimary !== '') {
            SettingsService::set('theme_primary_hex', $themePrimary);
        }
        $themeGradStart = self::normalizeHexInput($request->input('theme_gradient_start'));
        if ($themeGradStart !== '') {
            SettingsService::set('theme_gradient_start', $themeGradStart);
        }
        $themeGradEnd = self::normalizeHexInput($request->input('theme_gradient_end'));
        if ($themeGradEnd !== '') {
            SettingsService::set('theme_gradient_end', $themeGradEnd);
        }

        // Integrations: only update when value provided; never log secrets
        if ($request->filled('paystack_public_key')) {
            SettingsService::set('paystack_public_key', trim((string) $request->input('paystack_public_key')));
        }
        if ($request->filled('paystack_secret_key')) {
            SettingsService::set('paystack_secret_key', trim((string) $request->input('paystack_secret_key')));
        }
        if ($request->filled('sms_api_key')) {
            SettingsService::set('sms_api_key', trim((string) $request->input('sms_api_key')));
        }
        if ($request->has('sms_sender_id')) {
            SettingsService::set('sms_sender_id', trim((string) $request->input('sms_sender_id', '')));
        }
        if ($request->filled('refer2bundle_base_url')) {
            SettingsService::set('refer2bundle_base_url', rtrim(trim((string) $request->input('refer2bundle_base_url')), '/'));
        }
        if ($request->filled('refer2bundle_api_key')) {
            SettingsService::set('refer2bundle_api_key', trim((string) $request->input('refer2bundle_api_key')));
        }

        $many = [
            'maintenance_mode' => $maintenance ? '1' : '0',
            'otp_verification_enabled' => $request->boolean('otp_verification_enabled') ? '1' : '0',
            'affiliate_system_enabled' => $request->boolean('affiliate_system_enabled') ? '1' : '0',
            'manual_topup_enabled' => $manualTopupEnabled ? '1' : '0',
            'contact_phone' => $adminWhatsapp,
            'support_whatsapp_url' => $whatsappChatUrl,
        ];

        // Only include fields that are actually present in the request, so we
        // never overwrite an existing DB value with a null-turned-empty-string.
        foreach ([
            'site_name',
            'site_description',
            'min_withdraw',
            'txn_fee',
            'referral_bonus',
            'affiliate_signup_bonus',
            'affiliate_commission',
            'whatsapp_channel' => 'support_whatsapp_group_url',
            'support_email' => 'contact_email',
            'manual_payment_number' => 'manual_payment_mobile_money',
            'manual_payment_name' => 'manual_payment_account_name',
        ] as $reqKey => $dbKey) {
            if (is_int($reqKey)) {
                $reqKey = $dbKey;
            }
            if ($request->has($reqKey)) {
                $many[$dbKey] = (string) $request->input($reqKey, '');
            }
        }

        SettingsService::setMany($many);
        if ($request->has('platform_balance')) {
            SettingsService::set('platform_balance', (string) round((float) $request->input('platform_balance'), 2));
        }

        if ($request->has('smtp_host')) {
            SettingsService::set('smtp_host', (string) $request->input('smtp_host'));
        }
        if ($request->has('smtp_port')) {
            SettingsService::set('smtp_port', (string) $request->input('smtp_port', '587'));
        }
        if ($request->has('smtp_encryption')) {
            SettingsService::set('smtp_encryption', in_array($request->input('smtp_encryption'), ['tls', 'ssl'], true) ? $request->input('smtp_encryption') : '');
        }
        if ($request->has('smtp_username')) {
            SettingsService::set('smtp_username', (string) $request->input('smtp_username'));
        }
        if ($request->filled('smtp_password')) {
            SettingsService::set('smtp_password', (string) $request->input('smtp_password'));
        }
        if ($request->has('smtp_from_address')) {
            SettingsService::set('smtp_from_address', (string) $request->input('smtp_from_address'));
        }
        if ($request->has('smtp_from_name')) {
            SettingsService::set('smtp_from_name', (string) $request->input('smtp_from_name'));
        }
        if ($request->has('admin_notification_email')) {
            SettingsService::set('admin_notification_email', trim((string) $request->input('admin_notification_email')));
        }

        $all = SettingsService::all();
        $settings = [
            'site_name' => $all['site_name'] ?? config('app.name', 'R2BMarket'),
            'site_description' => $all['site_description'] ?? 'R2BMarket – Affordable mobile data bundles for MTN, AirtelTigo & Telecel in Ghana.',
            'site_logo_url' => SettingsService::getLogoUrl(),
            'site_favicon_url' => SettingsService::getFaviconUrl(),
            'theme_primary_hex' => $all['theme_primary_hex'] ?? '#9333ea',
            'theme_gradient_start' => $all['theme_gradient_start'] ?? '#a855f7',
            'theme_gradient_end' => $all['theme_gradient_end'] ?? '#6b21a8',
            'maintenance_mode' => ($all['maintenance_mode'] ?? '0') === '1',
            'otp_verification_enabled' => (isset($all['otp_verification_enabled']) && ($all['otp_verification_enabled'] === '1' || $all['otp_verification_enabled'] === 'true')),
            'affiliate_system_enabled' => (isset($all['affiliate_system_enabled']) && ($all['affiliate_system_enabled'] === '1' || $all['affiliate_system_enabled'] === 'true')),
            'min_withdraw' => $all['min_withdraw'] ?? '50.00',
            'txn_fee' => $all['txn_fee'] ?? '1.5',
            'referral_bonus' => $all['referral_bonus'] ?? '2.00',
            'affiliate_signup_bonus' => $all['affiliate_signup_bonus'] ?? '1.00',
            'affiliate_commission' => $all['affiliate_commission'] ?? '2.00',
            'admin_whatsapp' => $all['contact_phone'] ?? '',
            'whatsapp_channel' => $all['support_whatsapp_group_url'] ?? '',
            'support_email' => $all['contact_email'] ?? '',
            'manual_payment_number' => $all['manual_payment_mobile_money'] ?? '0241234567',
            'manual_payment_name' => $all['manual_payment_account_name'] ?? 'R2BMarket Official',
            'manual_topup_enabled' => (isset($all['manual_topup_enabled']) && ($all['manual_topup_enabled'] === '1' || $all['manual_topup_enabled'] === 'true')),
            'platform_balance' => $all['platform_balance'] ?? '0',
            'smtp_host' => $all['smtp_host'] ?? '',
            'smtp_port' => $all['smtp_port'] ?? '587',
            'smtp_encryption' => $all['smtp_encryption'] ?? 'tls',
            'smtp_username' => $all['smtp_username'] ?? '',
            'smtp_from_address' => $all['smtp_from_address'] ?? '',
            'smtp_from_name' => $all['smtp_from_name'] ?? config('app.name', 'R2BMarket'),
            'admin_notification_email' => $all['admin_notification_email'] ?? 'r2bmarketservices@gmail.com',
            'paystack_public_key' => $all['paystack_public_key'] ?? config('services.paystack.public_key', ''),
            'paystack_secret_key_set' => trim((string) ($all['paystack_secret_key'] ?? '')) !== '',
            'sms_sender_id' => $all['sms_sender_id'] ?? config('services.sms.sender_id', ''),
            'sms_api_key_set' => trim((string) ($all['sms_api_key'] ?? '')) !== '',
            'refer2bundle_base_url' => $all['refer2bundle_base_url'] ?? config('services.refer2bundle.base_url', ''),
            'refer2bundle_api_key_set' => trim((string) ($all['refer2bundle_api_key'] ?? '')) !== '',
        ];

        return response()->json([
            'message' => 'Settings updated successfully.',
            'settings' => $settings,
        ]);
    }

    /**
     * Send a test email using SMTP config from request body (test before saving) or from saved settings.
     */
    public function testEmail(Request $request)
    {
        $to = $request->input('to', $request->user()->email);
        if (! $to || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Valid "to" email is required.'], 422);
        }

        $all = SettingsService::all();
        $host = $request->filled('smtp_host') ? $request->input('smtp_host') : ($all['smtp_host'] ?? '');
        $port = $request->filled('smtp_port') ? (int) $request->input('smtp_port') : (int) ($all['smtp_port'] ?? 587);
        $encryption = $request->filled('smtp_encryption') ? $request->input('smtp_encryption') : ($all['smtp_encryption'] ?? 'tls');
        $username = $request->filled('smtp_username') ? $request->input('smtp_username') : ($all['smtp_username'] ?? '');
        $password = $request->filled('smtp_password') ? $request->input('smtp_password') : ($all['smtp_password'] ?? '');
        $fromAddress = $request->filled('smtp_from_address') ? $request->input('smtp_from_address') : ($all['smtp_from_address'] ?? $request->user()->email);
        $fromName = $request->filled('smtp_from_name') ? $request->input('smtp_from_name') : ($all['smtp_from_name'] ?? config('app.name', 'R2BMarket'));

        if (trim((string) $host) === '') {
            return response()->json(['error' => 'SMTP host is required. Enter it in Email (SMTP) settings and try again.'], 422);
        }

        $encryption = in_array($encryption, ['tls', 'ssl'], true) ? $encryption : null;

        try {
            config([
                'mail.mailers.dynamic_smtp' => [
                    'transport' => 'smtp',
                    'host' => $host,
                    'port' => $port,
                    'encryption' => $encryption,
                    'username' => $username,
                    'password' => $password,
                    'timeout' => 15,
                ],
                'mail.from' => ['address' => $fromAddress, 'name' => $fromName],
            ]);

            \Illuminate\Support\Facades\Mail::mailer('dynamic_smtp')
                ->raw("This is a test email from " . config('app.name', 'R2BMarket') . ".\n\nIf you received this, your SMTP configuration is working.", function ($message) use ($to) {
                    $message->to($to)->subject('SMTP test – ' . config('app.name', 'R2BMarket'));
                });

            return response()->json(['message' => 'Test email sent successfully to ' . $to . '. Check your inbox.']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('SMTP test failed', ['error' => $e->getMessage()]);
            $message = $e->getMessage();
            $hint = '';
            if (stripos($message, '535') !== false || stripos($message, 'BadCredentials') !== false || stripos($message, 'Username and Password not accepted') !== false) {
                $hint = ' For Gmail, use an App Password (not your normal password): Google Account → Security → 2-Step Verification → App passwords.';
            }
            return response()->json([
                'error' => 'Failed to send test email: ' . $message . $hint,
            ], 422);
        }
    }

    /**
     * Send announcement to users: in-app notification, email, and/or SMS.
     * Body: title, message, channels[] (in_app, email, sms), audience (all|users|resellers), user_ids[] (when audience=users).
     */
    public function sendAnnouncement(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'channels' => 'required|array',
            'channels.*' => 'string|in:in_app,email,sms',
            'audience' => 'required|string|in:all,users,resellers',
            'user_ids' => 'array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        if ($request->audience === 'users' && empty($request->user_ids)) {
            return response()->json(['error' => 'Select at least one user when audience is "users".'], 422);
        }

        $channels = array_values(array_unique($request->channels));
        if (empty($channels)) {
            return response()->json(['error' => 'Select at least one channel (In-app, Email, or SMS).'], 422);
        }

        if ($request->audience === 'all') {
            $users = User::all();
        } elseif ($request->audience === 'resellers') {
            $users = User::whereHas('resellerRequests', function ($q) {
                $q->where('status', ResellerRequest::STATUS_APPROVED);
            })->get();
        } else {
            $users = User::whereIn('id', $request->user_ids)->get();
        }

        $inApp = in_array('in_app', $channels, true);
        $email = in_array('email', $channels, true);
        $sms = in_array('sms', $channels, true);

        $inAppCount = 0;
        $emailCount = 0;
        $smsPhones = [];

        $bodyHtml = NotifyService::emailBody($request->title, $request->message, url('/'));

        foreach ($users as $user) {
            if ($inApp) {
                NotifyService::user($user, $request->title, $request->message, 'info', null);
                $inAppCount++;
            }
            if ($email) {
                NotifyService::sendUserEmail($user, $request->title, $bodyHtml);
                $emailCount++;
            }
            if ($sms && $user->phone) {
                $phone = \App\Support\GhanaPhone::normalize($user->phone);
                if ($phone) {
                    $smsPhones[] = $phone;
                }
            }
        }

        if ($sms && ! empty($smsPhones)) {
            NotifyService::sendAnnouncementSms($smsPhones, $request->message);
        }

        return response()->json([
            'message' => 'Announcement sent.',
            'in_app_count' => $inAppCount,
            'email_count' => $emailCount,
            'sms_count' => count($smsPhones),
        ]);
    }

    /**
     * List users for announcement recipient selection (id, full_name, email, phone).
     */
    public function getUsersForAnnouncement()
    {
        $users = User::orderBy('full_name')->get(['id', 'full_name', 'email', 'phone']);
        return response()->json($users);
    }

    // -------------------------------------------------------------------------
    // Affiliates (admin)
    // -------------------------------------------------------------------------

    /**
     * Users who are not yet affiliates (for "Add affiliate" dropdown).
     */
    public function getAffiliateEligibleUsers()
    {
        if (! Schema::hasTable('affiliates')) {
            return response()->json([]);
        }
        $ids = Affiliate::pluck('user_id')->all();
        $users = User::whereNotIn('id', $ids)->orderBy('full_name')->get(['id', 'full_name', 'email', 'phone']);
        return response()->json($users);
    }

    /**
     * List affiliates with user and withdrawal counts. Query params: search (name, email, referral_code), status, sort_by, sort_dir, export=1.
     */
    public function getAffiliates(Request $request)
    {
        if (! Schema::hasTable('affiliates')) {
            return response()->json(['data' => [], 'total' => 0]);
        }
        $query = Affiliate::with('user:id,full_name,email,phone')->withCount('withdrawals');
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('affiliates.referral_code', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('full_name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    });
            });
        }
        $status = $request->input('status');
        if ($status && in_array($status, [Affiliate::STATUS_PENDING, Affiliate::STATUS_APPROVED, Affiliate::STATUS_SUSPENDED], true)) {
            $query->where('affiliates.status', $status);
        }
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (in_array($sortBy, ['id', 'referral_code', 'balance', 'total_earned', 'total_withdrawn', 'created_at'], true)) {
            $query->orderBy('affiliates.' . $sortBy, $sortDir);
        } else {
            $query->orderByDesc('affiliates.created_at');
        }
        if ($request->boolean('export')) {
            $data = $query->get();
            return response()->json(['data' => $data, 'total' => $data->count()]);
        }
        $perPage = max(5, min(50, (int) $request->input('per_page', 20)));
        return response()->json($query->paginate($perPage));
    }

    /**
     * Create an affiliate from an existing user (by user_id). Generates unique referral code.
     */
    public function storeAffiliate(Request $request)
    {
        if (! Schema::hasTable('affiliates')) {
            return response()->json(['error' => 'Affiliates table not available.'], 503);
        }
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'referral_code' => 'nullable|string|max:32|unique:affiliates,referral_code',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'nullable|string|in:pending,approved,suspended',
        ]);
        if (Affiliate::where('user_id', $request->user_id)->exists()) {
            return response()->json(['error' => 'This user is already an affiliate.'], 422);
        }
        $code = $request->filled('referral_code')
            ? $request->referral_code
            : 'ref' . strtoupper(Str::random(8));
        if (Affiliate::where('referral_code', $code)->exists()) {
            $code = 'ref' . strtoupper(Str::random(10));
        }
        $affiliate = Affiliate::create([
            'user_id' => $request->user_id,
            'referral_code' => $code,
            'status' => $request->input('status', Affiliate::STATUS_PENDING),
            'commission_rate' => $request->input('commission_rate', 5.00),
        ]);
        $affiliate->load('user:id,full_name,email,phone');
        return response()->json(['message' => 'Affiliate created.', 'affiliate' => $affiliate], 201);
    }

    /**
     * Update affiliate (status, commission_rate).
     */
    public function updateAffiliate(Request $request, $id)
    {
        $affiliate = Affiliate::with('user')->find($id);
        if (! $affiliate) {
            return response()->json(['error' => 'Affiliate not found.'], 404);
        }
        $request->validate([
            'status' => 'nullable|string|in:pending,approved,suspended',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'referral_code' => 'nullable|string|max:32|unique:affiliates,referral_code,' . $id,
        ]);
        $updates = [];
        if ($request->has('status')) {
            $updates['status'] = $request->status;
        }
        if ($request->has('commission_rate')) {
            $updates['commission_rate'] = $request->commission_rate;
        }
        if ($request->filled('referral_code')) {
            $updates['referral_code'] = $request->referral_code;
        }
        if (! empty($updates)) {
            $affiliate->update($updates);
        }
        return response()->json(['message' => 'Affiliate updated.', 'affiliate' => $affiliate->fresh('user')]);
    }

    /**
     * List affiliate withdrawals. Query params: search (affiliate name/email), status, sort_by, sort_dir, export=1.
     */
    public function getAffiliateWithdrawals(Request $request)
    {
        if (! Schema::hasTable('affiliate_withdrawals')) {
            return response()->json(['data' => [], 'total' => 0]);
        }
        $query = AffiliateWithdrawal::with(['affiliate.user:id,full_name,email,phone', 'processor:id,full_name']);
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->whereHas('affiliate.user', function ($q) use ($search) {
                $q->where('full_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }
        $status = $request->input('status');
        if ($status && in_array($status, [AffiliateWithdrawal::STATUS_PENDING, AffiliateWithdrawal::STATUS_APPROVED, AffiliateWithdrawal::STATUS_REJECTED], true)) {
            $query->where('status', $status);
        }
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (in_array($sortBy, ['id', 'amount', 'status', 'created_at'], true)) {
            $query->orderBy('affiliate_withdrawals.' . $sortBy, $sortDir);
        } else {
            $query->orderByDesc('affiliate_withdrawals.created_at');
        }
        if ($request->boolean('export')) {
            $data = $query->get();
            return response()->json(['data' => $data, 'total' => $data->count()]);
        }
        $perPage = max(5, min(50, (int) $request->input('per_page', 20)));
        return response()->json($query->paginate($perPage));
    }

    /**
     * Get withdrawal detail: referred users and purchases that earned the affiliate (the earnings behind the requested amount).
     */
    public function getAffiliateWithdrawalDetail($id)
    {
        $withdrawal = AffiliateWithdrawal::with(['affiliate.user:id,full_name,email,phone'])->find($id);
        if (! $withdrawal) {
            return response()->json(['error' => 'Withdrawal not found.'], 404);
        }
        $affiliate = $withdrawal->affiliate;
        if (! $affiliate) {
            return response()->json(['error' => 'Affiliate not found.'], 404);
        }

        $referredUserIds = User::where('referred_by_affiliate_id', $affiliate->id)->pluck('id');
        $totalDepositsByUser = Topup::whereIn('user_id', $referredUserIds)
            ->where('status', 'approved')
            ->selectRaw('user_id, COALESCE(SUM(amount), 0) as total')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');
        $totalSpentByUser = Purchase::whereIn('user_id', $referredUserIds)
            ->selectRaw('user_id, COALESCE(SUM(amount), 0) as total')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $referredUsers = User::where('referred_by_affiliate_id', $affiliate->id)
            ->orderBy('created_at')
            ->get(['id', 'full_name', 'email', 'phone', 'created_at'])
            ->map(function (User $u) use ($totalDepositsByUser, $totalSpentByUser) {
                $deposits = $totalDepositsByUser->get($u->id);
                $spent = $totalSpentByUser->get($u->id);
                return [
                    'id' => $u->id,
                    'full_name' => $u->full_name,
                    'email' => $u->email,
                    'phone' => $u->phone,
                    'created_at' => $u->created_at?->toIso8601String(),
                    'total_deposits' => round((float) ($deposits ? $deposits->total : 0), 2),
                    'total_spent' => round((float) ($spent ? $spent->total : 0), 2),
                ];
            });

        $commissionRate = (float) (SettingsService::get('affiliate_commission', '2') ?: 0);
        $earningPurchases = Purchase::where('affiliate_commission_credited', true)
            ->whereHas('user', fn ($q) => $q->where('referred_by_affiliate_id', $affiliate->id))
            ->orderByDesc('created_at')
            ->with('user:id,full_name,email,phone')
            ->get(['id', 'user_id', 'reference', 'beneficiary', 'amount', 'status', 'created_at'])
            ->map(function (Purchase $p) use ($commissionRate) {
                $amount = (float) $p->amount;
                $commission = round($amount * ($commissionRate / 100), 2);
                return [
                    'id' => $p->id,
                    'reference' => $p->reference,
                    'beneficiary' => $p->beneficiary,
                    'amount' => round($amount, 2),
                    'commission' => $commission,
                    'status' => $p->status,
                    'created_at' => $p->created_at?->toIso8601String(),
                    'user' => $p->user ? [
                        'id' => $p->user->id,
                        'full_name' => $p->user->full_name,
                        'email' => $p->user->email,
                        'phone' => $p->user->phone,
                    ] : null,
                ];
            });

        $totalFromPurchases = $earningPurchases->sum(fn ($p) => (float) ($p['commission'] ?? 0));

        return response()->json([
            'withdrawal' => [
                'id' => $withdrawal->id,
                'amount' => round((float) $withdrawal->amount, 2),
                'status' => $withdrawal->status,
                'created_at' => $withdrawal->created_at?->toIso8601String(),
                'affiliate' => $affiliate ? [
                    'id' => $affiliate->id,
                    'balance' => round((float) $affiliate->balance, 2),
                    'total_earned' => round((float) $affiliate->total_earned, 2),
                    'user' => $affiliate->user ? [
                        'id' => $affiliate->user->id,
                        'full_name' => $affiliate->user->full_name,
                        'email' => $affiliate->user->email,
                        'phone' => $affiliate->user->phone,
                    ] : null,
                ] : null,
            ],
            'referred_users' => $referredUsers->values()->all(),
            'earning_purchases' => $earningPurchases->values()->all(),
            'commission_rate' => $commissionRate,
            'total_earned_from_purchases' => round($totalFromPurchases, 2),
        ]);
    }

    /**
     * Approve an affiliate withdrawal: deduct from affiliate balance, mark as approved.
     */
    public function approveAffiliateWithdrawal(Request $request, $id)
    {
        $withdrawal = AffiliateWithdrawal::with('affiliate.user')->find($id);
        if (! $withdrawal) {
            return response()->json(['error' => 'Withdrawal not found.'], 404);
        }
        if ($withdrawal->status !== AffiliateWithdrawal::STATUS_PENDING) {
            return response()->json(['error' => 'Withdrawal is no longer pending.'], 400);
        }
        $affiliate = $withdrawal->affiliate;
        $amount = (float) $withdrawal->amount;
        $minWithdraw = (float) (SettingsService::get('min_withdraw', '50') ?: 50);
        if ($amount < $minWithdraw) {
            return response()->json(['error' => 'Withdrawal amount is below the minimum allowed (GHS ' . number_format($minWithdraw, 2) . ').'], 422);
        }
        if ((float) $affiliate->balance < $amount) {
            return response()->json(['error' => 'Affiliate balance is insufficient for this withdrawal.'], 422);
        }
        $txnFeePct = (float) (SettingsService::get('txn_fee', '1.5') ?: 0);
        $feeAmount = round($amount * ($txnFeePct / 100), 2);
        $affiliate->decrement('balance', $amount);
        $affiliate->increment('total_withdrawn', $amount);
        $withdrawal->update([
            'status' => AffiliateWithdrawal::STATUS_APPROVED,
            'fee_amount' => $feeAmount,
            'admin_notes' => $request->input('admin_notes'),
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);
        $withdrawal->load('affiliate.user');
        if ($withdrawal->affiliate && $withdrawal->affiliate->user) {
            NotifyService::notifyUserWithdrawalApproved($withdrawal->affiliate->user, $withdrawal->fresh());
        }
        return response()->json([
            'message' => 'Withdrawal approved.',
            'withdrawal' => $withdrawal->fresh(['affiliate.user', 'processor']),
        ]);
    }

    /**
     * Reject an affiliate withdrawal.
     */
    public function rejectAffiliateWithdrawal(Request $request, $id)
    {
        $withdrawal = AffiliateWithdrawal::with('affiliate.user')->find($id);
        if (! $withdrawal) {
            return response()->json(['error' => 'Withdrawal not found.'], 404);
        }
        if ($withdrawal->status !== AffiliateWithdrawal::STATUS_PENDING) {
            return response()->json(['error' => 'Withdrawal is no longer pending.'], 400);
        }
        $withdrawal->update([
            'status' => AffiliateWithdrawal::STATUS_REJECTED,
            'admin_notes' => $request->input('admin_notes'),
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);
        $withdrawal->load('affiliate.user');
        if ($withdrawal->affiliate && $withdrawal->affiliate->user) {
            NotifyService::notifyUserWithdrawalRejected($withdrawal->affiliate->user, $withdrawal->fresh());
        }
        return response()->json([
            'message' => 'Withdrawal rejected.',
            'withdrawal' => $withdrawal->fresh(['affiliate.user', 'processor']),
        ]);
    }

    /**
     * List reseller (wallet) withdrawal requests. Query: search, status, sort_by, sort_dir, export=1.
     */
    public function getResellerWithdrawals(Request $request)
    {
        if (! Schema::hasTable('reseller_withdrawals')) {
            return response()->json(['data' => [], 'total' => 0]);
        }
        $query = ResellerWithdrawal::with(['user:id,full_name,email,phone', 'processor:id,full_name']);
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('full_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }
        $status = $request->input('status');
        if ($status && in_array($status, [ResellerWithdrawal::STATUS_PENDING, ResellerWithdrawal::STATUS_APPROVED, ResellerWithdrawal::STATUS_REJECTED], true)) {
            $query->where('status', $status);
        }
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (in_array($sortBy, ['id', 'amount', 'status', 'created_at'], true)) {
            $query->orderBy('reseller_withdrawals.' . $sortBy, $sortDir);
        } else {
            $query->orderByDesc('reseller_withdrawals.created_at');
        }
        if ($request->boolean('export')) {
            $data = $query->get();

            return response()->json(['data' => $data, 'total' => $data->count()]);
        }
        $perPage = max(5, min(50, (int) $request->input('per_page', 20)));

        return response()->json($query->paginate($perPage));
    }

    /**
     * Context for a reseller withdrawal: user balance and store sales that credited profit.
     */
    public function getResellerWithdrawalDetail($id)
    {
        $withdrawal = ResellerWithdrawal::with(['user:id,full_name,email,phone,balance,reseller_earnings_balance'])->find($id);
        if (! $withdrawal) {
            return response()->json(['error' => 'Withdrawal not found.'], 404);
        }
        $user = $withdrawal->user;
        if (! $user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        $creditedSales = Purchase::where('reseller_id', $user->id)
            ->where('reseller_profit_credited', true)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get(['id', 'reference', 'beneficiary', 'amount', 'reseller_profit', 'status', 'created_at'])
            ->map(function (Purchase $p) {
                return [
                    'id' => $p->id,
                    'reference' => $p->reference,
                    'beneficiary' => $p->beneficiary,
                    'amount' => round((float) $p->amount, 2),
                    'reseller_profit' => $p->reseller_profit !== null ? round((float) $p->reseller_profit, 2) : 0,
                    'status' => $p->status,
                    'created_at' => $p->created_at?->toIso8601String(),
                ];
            });

        $profitTotal = (float) Purchase::where('reseller_id', $user->id)
            ->where('reseller_profit_credited', true)
            ->sum('reseller_profit');

        return response()->json([
            'withdrawal' => [
                'id' => $withdrawal->id,
                'amount' => round((float) $withdrawal->amount, 2),
                'momo_number' => $withdrawal->momo_number ? (string) $withdrawal->momo_number : null,
                'status' => $withdrawal->status,
                'created_at' => $withdrawal->created_at?->toIso8601String(),
                'user' => [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'balance' => round((float) $user->balance, 2),
                    'reseller_withdrawable_balance' => Schema::hasColumn('users', 'reseller_earnings_balance')
                        ? round(max(0, (float) $user->reseller_earnings_balance), 2)
                        : round((float) $user->balance, 2),
                ],
            ],
            'credited_sales' => $creditedSales->values()->all(),
            'total_credited_profit' => round($profitTotal, 2),
        ]);
    }

    public function approveResellerWithdrawal(Request $request, $id)
    {
        if (! Schema::hasTable('reseller_withdrawals')) {
            return response()->json(['error' => 'Withdrawals not available.'], 503);
        }

        $minWithdraw = (float) (SettingsService::get('min_withdraw', '50') ?: 50);
        $txnFeePct = (float) (SettingsService::get('txn_fee', '1.5') ?: 0);

        try {
            DB::transaction(function () use ($request, $id, $minWithdraw, $txnFeePct) {
                $w = ResellerWithdrawal::whereKey($id)->lockForUpdate()->first();
                if (! $w) {
                    throw new \RuntimeException('withdrawal_not_found');
                }
                if ($w->status !== ResellerWithdrawal::STATUS_PENDING) {
                    throw new \RuntimeException('withdrawal_not_pending');
                }
                $amount = (float) $w->amount;
                if ($amount < $minWithdraw) {
                    throw new \RuntimeException('below_min');
                }
                $feeAmount = round($amount * ($txnFeePct / 100), 2);
                $lockedUser = User::whereKey($w->user_id)->lockForUpdate()->first();
                if (! $lockedUser) {
                    throw new \RuntimeException('user_not_found');
                }
                if ((float) $lockedUser->balance < $amount) {
                    throw new \RuntimeException('insufficient_balance');
                }
                if (Schema::hasColumn('users', 'reseller_earnings_balance')
                    && (float) $lockedUser->reseller_earnings_balance < $amount) {
                    throw new \RuntimeException('insufficient_reseller_earnings');
                }
                $lockedUser->decrement('balance', $amount);
                if (Schema::hasColumn('users', 'reseller_earnings_balance')) {
                    $lockedUser->decrement('reseller_earnings_balance', $amount);
                }
                $w->update([
                    'status' => ResellerWithdrawal::STATUS_APPROVED,
                    'fee_amount' => $feeAmount,
                    'admin_notes' => $request->input('admin_notes'),
                    'processed_by' => $request->user()->id,
                    'processed_at' => now(),
                ]);
            });
        } catch (\RuntimeException $e) {
            return match ($e->getMessage()) {
                'withdrawal_not_found' => response()->json(['error' => 'Withdrawal not found.'], 404),
                'withdrawal_not_pending' => response()->json(['error' => 'Withdrawal is no longer pending.'], 400),
                'below_min' => response()->json([
                    'error' => 'Withdrawal amount is below the minimum allowed (GHS ' . number_format($minWithdraw, 2) . ').',
                ], 422),
                'user_not_found' => response()->json(['error' => 'User not found.'], 404),
                'insufficient_balance' => response()->json(['error' => 'Reseller wallet balance is insufficient for this withdrawal.'], 422),
                'insufficient_reseller_earnings' => response()->json([
                    'error' => 'Reseller withdrawable (profit) balance is too low — they may have spent profit or the record is out of sync. Reject and ask them to retry.',
                ], 422),
                default => throw $e,
            };
        }

        $withdrawal = ResellerWithdrawal::with('user')->find($id);
        if ($withdrawal && $withdrawal->user) {
            NotifyService::notifyUserResellerWithdrawalApproved($withdrawal->user, $withdrawal->fresh());
        }

        return response()->json([
            'message' => 'Withdrawal approved.',
            'withdrawal' => ResellerWithdrawal::with(['user', 'processor'])->find($id),
        ]);
    }

    public function rejectResellerWithdrawal(Request $request, $id)
    {
        if (! Schema::hasTable('reseller_withdrawals')) {
            return response()->json(['error' => 'Withdrawals not available.'], 503);
        }

        try {
            DB::transaction(function () use ($request, $id) {
                $w = ResellerWithdrawal::whereKey($id)->lockForUpdate()->first();
                if (! $w) {
                    throw new \RuntimeException('withdrawal_not_found');
                }
                if ($w->status !== ResellerWithdrawal::STATUS_PENDING) {
                    throw new \RuntimeException('withdrawal_not_pending');
                }
                $w->update([
                    'status' => ResellerWithdrawal::STATUS_REJECTED,
                    'admin_notes' => $request->input('admin_notes'),
                    'processed_by' => $request->user()->id,
                    'processed_at' => now(),
                ]);
            });
        } catch (\RuntimeException $e) {
            return match ($e->getMessage()) {
                'withdrawal_not_found' => response()->json(['error' => 'Withdrawal not found.'], 404),
                'withdrawal_not_pending' => response()->json(['error' => 'Withdrawal is no longer pending.'], 400),
                default => throw $e,
            };
        }

        $withdrawal = ResellerWithdrawal::with('user')->find($id);
        if ($withdrawal && $withdrawal->user) {
            NotifyService::notifyUserResellerWithdrawalRejected($withdrawal->user, $withdrawal->fresh());
        }

        return response()->json([
            'message' => 'Withdrawal rejected.',
            'withdrawal' => $withdrawal ? $withdrawal->fresh(['user', 'processor']) : null,
        ]);
    }

    /** Normalize hex color input: trim, ensure # prefix, validate 3 or 6 hex digits. */
    private static function normalizeHexInput(?string $value): string
    {
        $value = preg_replace('/^\s+|\s+$/', '', (string) $value);
        $value = preg_replace('/^#/', '', $value);
        if (preg_match('/^[0-9A-Fa-f]{6}$/', $value)) {
            return '#' . $value;
        }
        if (preg_match('/^[0-9A-Fa-f]{3}$/', $value)) {
            return '#' . $value[0] . $value[0] . $value[1] . $value[1] . $value[2] . $value[2];
        }
        return '';
    }
}
