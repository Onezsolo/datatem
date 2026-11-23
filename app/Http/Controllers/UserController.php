<?php

namespace App\Http\Controllers;

use App\Models\DataPackage;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserController extends Controller
{
    /**
     * Get user statistics for dashboard (data orders = purchases).
     */
    public function getStats()
    {
        $userId = auth('api')->id();
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $stats = [
            'balance' => $user->balance,
            'todaySpent' => Purchase::where('user_id', $userId)
                ->where('status', 'delivered')
                ->whereDate('created_at', Carbon::today())
                ->sum('amount'),
            'totalSpent' => Purchase::where('user_id', $userId)
                ->where('status', 'delivered')
                ->sum('amount'),
            'totalOrders' => Purchase::where('user_id', $userId)->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get unified transaction history (Data purchases + Topups) with pagination.
     */
    public function getTransactions(Request $request)
    {
        $userId = auth('api')->id();
        $perPage = max(5, min(50, (int) $request->input('per_page', 20)));
        $page = max(1, (int) $request->input('page', 1));
        $type = $request->input('type', 'all'); // all|data|topup

        // Fetch data purchases (orders) if needed
        $orders = collect();
        if ($type === 'all' || $type === 'data') {
            $orders = Purchase::with('dataPackage')->where('user_id', $userId)
                ->get()
                ->map(function ($order) {
                $networkRaw = $order->dataPackage?->network ?? $order->network ?? 'MTN';
                $logo = $this->networkToLogoKey($networkRaw);

                $status = $order->status;
                $statusDisplay = $status === 'delivered' ? 'Completed' : ($status === 'failed' ? 'Failed' : 'Pending');

                return [
                    'id' => '#ORD-' . $order->id,
                    'type' => 'data',
                    'description' => $order->data_plan ?? $order->network,
                    'number' => $order->beneficiary ?? '',
                    'date' => $order->created_at->format('M d, Y'),
                    'full_date' => $order->created_at->toDateTimeString(),
                    'amount' => 'GH₵ ' . number_numeric($order->amount),
                    'raw_amount' => $order->amount,
                    'status' => $statusDisplay,
                    'logo' => $logo,
                ];
            });
        }

        // Fetch Topups if needed
        $topups = collect();
        if ($type === 'all' || $type === 'topup') {
            $topups = \App\Models\Topup::where('user_id', $userId)
            ->get()
            ->map(function ($topup) {
                return [
                    'id' => '#TP-' . $topup->id,
                    'type' => 'topup',
                    'description' => 'Wallet Topup via ' . ucfirst($topup->method),
                    'date' => $topup->created_at->format('M d, Y'),
                    'full_date' => $topup->created_at->toDateTimeString(),
                    'amount' => 'GH₵ ' . number_numeric($topup->amount),
                    'raw_amount' => $topup->amount,
                    'status' => ucfirst($topup->status),
                ];
            });
        }

        // Merge (if all) and sort, then paginate
        $all = $type === 'data'
            ? $orders->sortByDesc('full_date')->values()
            : ($type === 'topup'
                ? $topups->sortByDesc('full_date')->values()
                : $orders->concat($topups)->sortByDesc('full_date')->values());
        $total = $all->count();
        $lastPage = (int) max(1, ceil($total / $perPage));
        $page = min($page, $lastPage);
        $slice = $all->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data' => $slice,
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }

    /**
     * Map network name to logo key for transaction list (MTN, AT, Telecel).
     */
    private function networkToLogoKey(string $network): string
    {
        $n = strtoupper(trim($network));
        if (str_contains($n, 'MTN')) {
            return 'MTN';
        }
        if (str_contains($n, 'TELECEL') || str_contains($n, 'VODAFONE')) {
            return 'Telecel';
        }
        if (str_contains($n, 'AIRTEL') || str_contains($n, 'TIGO') || $n === 'AT') {
            return 'AT';
        }
        return 'MTN';
    }

    /**
     * Update authenticated user profile (full_name, phone).
     */
    public function updateProfile(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user->full_name = trim((string) $request->full_name);
        $user->phone = $request->filled('phone') ? trim((string) $request->phone) : null;
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Change authenticated user's password.
     */
    public function changePassword(Request $request)
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.confirmed' => 'The new password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Current password is incorrect.'], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }

    /**
     * Delete authenticated user's account. Requires password confirmation.
     */
    public function deleteAccount(Request $request)
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (! Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Password is incorrect.'], 422);
        }

        $userId = $user->id;
        auth('api')->logout();
        User::where('id', $userId)->delete();

        return response()->json(['message' => 'Account deleted successfully.']);
    }

    /**
     * Get current user's in-app notifications (works for both users and admins).
     */
    public function getNotifications(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $perPage = max(1, min(50, (int) $request->query('per_page', 30)));
        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $items = $notifications->getCollection()->map(function ($n) {
            $data = $n->data;
            return [
                'id' => $n->id,
                'title' => $data['title'] ?? 'Notification',
                'message' => $data['message'] ?? '',
                'type' => $data['type'] ?? 'info',
                'url' => $data['url'] ?? null,
                'meta' => $data['meta'] ?? [],
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
                'created_at_human' => $n->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'data' => $items->values()->all(),
            'unread_count' => $user->unreadNotifications()->count(),
            'links' => $notifications->linkCollection()->toArray(),
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markNotificationRead(Request $request, string $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $user->notifications()->where('id', $id)->update(['read_at' => now()]);
        return response()->json(['ok' => true, 'unread_count' => $user->unreadNotifications()->count()]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllNotificationsRead(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $user->unreadNotifications()->update(['read_at' => now()]);
        return response()->json(['ok' => true, 'unread_count' => 0]);
    }

    /**
     * Popular packages for dashboard: most affordable 3 per provider (MTN, AT, Telecel).
     */
    public function getPopularPackages()
    {
        if (!auth('api')->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $priceCol = \DB::raw('COALESCE(selling_price, console_price)');

        $mtn = DataPackage::available()
            ->excludeExpressNetworks()
            ->excludeProviderBundleIds()
            ->where(function ($q) {
                $q->where('network', 'like', '%MTN%');
            })
            ->orderBy($priceCol)
            ->limit(3)
            ->get()
            ->map(fn ($p) => $this->formatPackageForDashboard($p, 'MTN'));

        $at = DataPackage::available()
            ->excludeExpressNetworks()
            ->excludeProviderBundleIds()
            ->where(function ($q) {
                $q->where('network', 'like', '%AT%')->orWhere('network', 'like', '%Airtel%');
            })
            ->orderBy($priceCol)
            ->limit(3)
            ->get()
            ->map(fn ($p) => $this->formatPackageForDashboard($p, 'AT'));

        $telecel = DataPackage::available()
            ->excludeExpressNetworks()
            ->excludeProviderBundleIds()
            ->where(function ($q) {
                $q->where('network', 'like', '%Telecel%')->orWhere('network', 'like', '%Vodafone%');
            })
            ->orderBy($priceCol)
            ->limit(3)
            ->get()
            ->map(fn ($p) => $this->formatPackageForDashboard($p, 'Telecel'));

        return response()->json([
            'mtn' => $mtn->values()->all(),
            'at' => $at->values()->all(),
            'telecel' => $telecel->values()->all(),
        ]);
    }

    private function formatPackageForDashboard(DataPackage $p, string $providerKey): array
    {
        $price = $p->selling_price !== null ? (float) $p->selling_price : (float) $p->console_price;
        $name = $p->volume_gb ?: ($p->volume ? $p->volume . ' GB' : 'Data');
        return [
            'id' => $p->id,
            'network' => $providerKey,
            'name' => $name,
            'plan_label' => $p->plan_label,
            'price' => $price,
            'price_formatted' => 'GH₵ ' . number_format($price, 2),
            'volume_gb' => $p->volume_gb ?? (string) $p->volume,
        ];
    }
}

/**
 * Helper to format numeric strings
 */
function number_numeric($val) {
    return number_format((float)$val, 2, '.', ',');
}
