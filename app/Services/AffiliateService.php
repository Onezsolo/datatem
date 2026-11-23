<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AffiliateService
{
    /**
     * Ensure the user has an affiliate record (every user is an affiliate). Creates one with a unique referral code if missing.
     */
    public static function ensureAffiliateForUser(User $user): Affiliate
    {
        $affiliate = Affiliate::where('user_id', $user->id)->first();
        if ($affiliate) {
            return $affiliate;
        }

        $code = 'ref' . strtoupper(Str::random(8));
        while (Affiliate::where('referral_code', $code)->exists()) {
            $code = 'ref' . strtoupper(Str::random(10));
        }

        return Affiliate::create([
            'user_id' => $user->id,
            'referral_code' => $code,
            'status' => Affiliate::STATUS_APPROVED,
            'commission_rate' => 0,
        ]);
    }

    /**
     * Credit the referrer (affiliate) when someone signs up with their referral link.
     * Amount from setting affiliate_signup_bonus (e.g. 1.00 GHS).
     */
    public static function creditSignupBonus(int $affiliateId): void
    {
        if (! SettingsService::getBool('affiliate_system_enabled')) {
            return;
        }
        $affiliate = Affiliate::where('id', $affiliateId)->where('status', Affiliate::STATUS_APPROVED)->first();
        if (! $affiliate) {
            return;
        }
        $amount = (float) (SettingsService::get('affiliate_signup_bonus', '0') ?: 0);
        if ($amount <= 0) {
            return;
        }
        $affiliate->increment('balance', $amount);
        $affiliate->increment('total_earned', $amount);
    }

    /**
     * Credit the referrer (affiliate) when a referred user's purchase is delivered.
     * Commission = purchase amount × (affiliate_commission% / 100).
     * Safe to call multiple times: guarded by affiliate_commission_credited on the purchase.
     */
    public static function creditCommissionForPurchase(Purchase $purchase): void
    {
        if (! SettingsService::getBool('affiliate_system_enabled')) {
            return;
        }
        $purchase->load('user');
        $user = $purchase->user;
        if (! $user || $user->referred_by_affiliate_id === null) {
            return;
        }

        $affiliate = Affiliate::where('id', $user->referred_by_affiliate_id)->first();
        if (! $affiliate || $affiliate->status !== Affiliate::STATUS_APPROVED) {
            return;
        }

        if ($purchase->affiliate_commission_credited) {
            return;
        }

        $rate = (float) (SettingsService::get('affiliate_commission', '2') ?: 0);
        if ($rate <= 0) {
            return;
        }

        $amount = (float) $purchase->amount;
        $commission = round($amount * ($rate / 100), 2);
        if ($commission <= 0) {
            return;
        }

        DB::transaction(function () use ($purchase, $affiliate, $commission): void {
            $locked = Purchase::where('id', $purchase->id)->lockForUpdate()->first();
            if (! $locked || $locked->affiliate_commission_credited) {
                return;
            }
            Affiliate::where('id', $affiliate->id)->lockForUpdate()->first();
            $affiliate->increment('balance', $commission);
            $affiliate->increment('total_earned', $commission);
            $locked->update(['affiliate_commission_credited' => true]);
        });
    }
}
