<?php

namespace App\Console;

use App\Models\Affiliate;
use App\Models\User;
use App\Services\AffiliateService;
use App\Services\SettingsService;
use Illuminate\Console\Command;

class BackfillReferrerSignupBonus extends Command
{
    protected $signature = 'affiliate:backfill-signup-bonus';

    protected $description = 'Credit referrers with signup bonus for existing referred users who were not credited yet.';

    public function handle(): int
    {
        if (! SettingsService::getBool('affiliate_system_enabled')) {
            $this->warn('Affiliate system is disabled. Enable it in settings first.');
            return self::FAILURE;
        }

        $users = User::whereNotNull('referred_by_affiliate_id')
            ->where('referrer_signup_bonus_paid', false)
            ->get();

        if ($users->isEmpty()) {
            $this->info('No referred users to backfill.');
            return self::SUCCESS;
        }

        $bonus = (float) (SettingsService::get('affiliate_signup_bonus', '1') ?: 0);
        if ($bonus <= 0) {
            $this->warn('Affiliate signup bonus is 0. Set it in Admin → Settings → Finance & Fees.');
            return self::FAILURE;
        }

        $count = 0;
        foreach ($users as $user) {
            AffiliateService::creditSignupBonus((int) $user->referred_by_affiliate_id);
            $user->update(['referrer_signup_bonus_paid' => true]);
            $count++;
        }

        $this->info("Credited signup bonus for {$count} referred user(s).");
        return self::SUCCESS;
    }
}
