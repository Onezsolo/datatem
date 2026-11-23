<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\AffiliateWithdrawal;
use App\Models\User;
use App\Services\AffiliateService;
use App\Services\NotifyService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    /**
     * Get current user's affiliate dashboard: stats + withdrawal list (only if they are an affiliate).
     */
    public function index(Request $request): JsonResponse
    {
        if (! SettingsService::getBool('affiliate_system_enabled')) {
            return response()->json(['error' => 'Affiliate program is not available.'], 403);
        }

        $user = $request->user();
        $affiliate = AffiliateService::ensureAffiliateForUser($user);

        $withdrawals = AffiliateWithdrawal::where('affiliate_id', $affiliate->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $referralsCount = User::where('referred_by_affiliate_id', $affiliate->id)->count();

        return response()->json([
            'affiliate' => [
                'id' => $affiliate->id,
                'referral_code' => $affiliate->referral_code,
                'balance' => $affiliate->balance,
                'total_earned' => $affiliate->total_earned,
                'total_withdrawn' => $affiliate->total_withdrawn,
                'status' => $affiliate->status,
                'referrals_count' => $referralsCount,
            ],
            'withdrawals' => $withdrawals->map(fn ($w) => [
                'id' => $w->id,
                'amount' => $w->amount,
                'fee_amount' => $w->fee_amount,
                'status' => $w->status,
                'admin_notes' => $w->admin_notes,
                'created_at' => $w->created_at?->toIso8601String(),
                'processed_at' => $w->processed_at?->toIso8601String(),
            ]),
            'min_withdraw' => (float) (SettingsService::get('min_withdraw', '50') ?: 50),
        ]);
    }

    /**
     * Request a withdrawal (affiliate only). Submits to admin for approval.
     */
    public function requestWithdrawal(Request $request): JsonResponse
    {
        if (! SettingsService::getBool('affiliate_system_enabled')) {
            return response()->json(['error' => 'Affiliate program is not available.'], 403);
        }

        $user = $request->user();
        $affiliate = AffiliateService::ensureAffiliateForUser($user);
        if ($affiliate->status !== Affiliate::STATUS_APPROVED) {
            return response()->json(['error' => 'Your affiliate account is not approved for withdrawals.'], 403);
        }

        $minWithdraw = (float) (SettingsService::get('min_withdraw', '50') ?: 50);
        $request->validate([
            'amount' => 'required|numeric|min:' . $minWithdraw,
        ]);

        $amount = round((float) $request->amount, 2);
        $balance = (float) $affiliate->balance;

        if ($amount > $balance) {
            return response()->json(['error' => 'Insufficient balance. Your balance is GH₵ ' . number_format($balance, 2) . '.'], 422);
        }

        $withdrawal = AffiliateWithdrawal::create([
            'affiliate_id' => $affiliate->id,
            'amount' => $amount,
            'status' => AffiliateWithdrawal::STATUS_PENDING,
        ]);

        NotifyService::notifyAdminsAffiliateWithdrawalRequested($withdrawal);

        return response()->json([
            'message' => 'Withdrawal request submitted. You will be notified when it is processed.',
            'withdrawal' => [
                'id' => $withdrawal->id,
                'amount' => $withdrawal->amount,
                'status' => $withdrawal->status,
                'created_at' => $withdrawal->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
