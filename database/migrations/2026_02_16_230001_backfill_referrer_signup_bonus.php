<?php

use App\Models\Affiliate;
use App\Models\User;
use App\Services\AffiliateService;
use App\Services\SettingsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'referrer_signup_bonus_paid')) {
            return;
        }
        if (! SettingsService::getBool('affiliate_system_enabled')) {
            return;
        }
        $bonus = (float) (SettingsService::get('affiliate_signup_bonus', '1') ?: 0);
        if ($bonus <= 0) {
            return;
        }

        $userIds = DB::table('users')
            ->whereNotNull('referred_by_affiliate_id')
            ->where('referrer_signup_bonus_paid', false)
            ->pluck('id');

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (! $user || ! $user->referred_by_affiliate_id) {
                continue;
            }
            AffiliateService::creditSignupBonus((int) $user->referred_by_affiliate_id);
            $user->update(['referrer_signup_bonus_paid' => true]);
        }
    }

    public function down(): void
    {
        // No reverse – we don't remove credits
    }
};
