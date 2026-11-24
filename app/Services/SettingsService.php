<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class SettingsService
{
    private const CACHE_KEY = 'app_settings';
    private const CACHE_TTL_SECONDS = 300;

    /** Default/fallback values when not in DB (from config or hardcoded). */
    private static function fallbacks(): array
    {
        return [
            'otp_verification_enabled' => '1',
            'paystack_public_key' => (string) config('services.paystack.public_key', ''),
            'paystack_secret_key' => (string) config('services.paystack.secret_key', ''),
            'sms_api_key' => (string) config('services.sms.api_key', ''),
            'sms_sender_id' => (string) config('services.sms.sender_id', 'R2BMarket'),
            'jaybart_base_url' => (string) config('services.jaybart.base_url', 'https://agent.jaybartservices.com/api/v1'),
            'jaybart_api_key' => (string) config('services.jaybart.api_key', ''),
            'refer2bundle_base_url' => (string) config('services.refer2bundle.base_url', 'https://databosshub.org/api/v1'),
            'refer2bundle_api_key' => (string) config('services.refer2bundle.api_key', ''),
            'site_name' => (string) config('app.name', 'Datatem'),
            'site_description' => 'Mobile data bundles and everyday shopping in Ghana. Top up your wallet and get instant delivery.',
            'site_logo' => '',
            'site_favicon' => '',
            'theme_primary_hex' => '#9333ea',
            'theme_gradient_start' => '#a855f7',
            'theme_gradient_end' => '#6b21a8',
            'contact_email' => '',
            'contact_phone' => '',
            'support_whatsapp_url' => 'https://wa.me/233544080650',
            'support_whatsapp_group_url' => 'https://chat.whatsapp.com/F3CjhD8Ioya7JG7nki7xQJ',
            'manual_topup_enabled' => '0',
            'manual_payment_bank_name' => '',
            'manual_payment_account_number' => '',
            'manual_payment_mobile_money' => '0241234567',
            'manual_payment_account_name' => 'R2BMarket Official',
            'manual_payment_instructions' => '',
            'maintenance_mode' => '0',
            'affiliate_system_enabled' => '1',
            'min_withdraw' => '50.00',
            'txn_fee' => '1.5',
            'referral_bonus' => '2.00',
            'affiliate_signup_bonus' => '1.00',
            'affiliate_commission' => '2.00',
            'platform_balance' => '0',
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_encryption' => 'tls',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_from_address' => '',
            'smtp_from_name' => (string) config('app.name', 'R2BMarket'),
            'admin_notification_email' => 'r2bmarketservices@gmail.com',
        ];
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = self::all();
        $value = $all[$key] ?? $default;
        return $value === null ? null : (string) $value;
    }

    public static function getBool(string $key): bool
    {
        $v = self::get($key);
        return $v === '1' || $v === 'true' || $v === 'yes';
    }

    /** @return array<string, string> */
    public static function all(): array
    {
        if (! \Schema::hasTable('settings')) {
            return self::fallbacks();
        }

        $cached = Cache::get(self::CACHE_KEY);
        if (\is_array($cached)) {
            return array_merge(self::fallbacks(), $cached);
        }

        $rows = DB::table('settings')->pluck('value', 'key')->all();
        Cache::put(self::CACHE_KEY, $rows, self::CACHE_TTL_SECONDS);
        return array_merge(self::fallbacks(), $rows);
    }

    public static function set(string $key, ?string $value): void
    {
        if (! \Schema::hasTable('settings')) {
            return;
        }
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
        Cache::forget(self::CACHE_KEY);
    }

    /** @param array<string, string|null> $pairs */
    public static function setMany(array $pairs): void
    {
        if (! \Schema::hasTable('settings')) {
            return;
        }
        $values = [];
        foreach ($pairs as $key => $value) {
            $values[$key] = $value === null ? '' : (string) $value;
        }
        foreach ($values as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now()]
            );
        }
        Cache::forget(self::CACHE_KEY);
    }

    /** Get platform balance (float). Used for storefront orders. */
    public static function getPlatformBalance(): float
    {
        return (float) (self::get('platform_balance', '0') ?: 0);
    }

    /** Decrement platform balance by amount. Returns true if successful (balance was sufficient). */
    public static function decrementPlatformBalance(float $amount): bool
    {
        $current = self::getPlatformBalance();
        if ($current < $amount) {
            return false;
        }
        self::set('platform_balance', (string) round($current - $amount, 2));
        return true;
    }

    /** Increment platform balance (e.g. admin top-up). */
    public static function incrementPlatformBalance(float $amount): void
    {
        $current = self::getPlatformBalance();
        self::set('platform_balance', (string) round($current + $amount, 2));
    }

    /** Keys that are safe to expose to frontend (no secrets). */
    public static function publicKeys(): array
    {
        return [
            'otp_verification_enabled',
            'paystack_public_key',
            'site_name',
            'site_description',
            'site_logo',
            'site_favicon',
            'theme_primary_hex',
            'theme_gradient_start',
            'theme_gradient_end',
            'contact_email',
            'contact_phone',
            'support_whatsapp_url',
            'support_whatsapp_group_url',
            'manual_topup_enabled',
            'manual_payment_bank_name',
            'manual_payment_account_number',
            'manual_payment_mobile_money',
            'manual_payment_account_name',
            'manual_payment_instructions',
            'affiliate_system_enabled',
        ];
    }

    /** Full URL for site logo (for use in layouts). Empty setting = default asset. */
    public static function getLogoUrl(): string
    {
        $path = self::get('site_logo', '');
        return $path !== '' ? asset('storage/' . $path) : asset('assets/dev-esccay-mark.svg');
    }

    /** Full URL for site favicon (for use in layouts). Empty setting = default asset. */
    public static function getFaviconUrl(): string
    {
        $path = self::get('site_favicon', '');
        return $path !== '' ? asset('storage/' . $path) : asset('assets/dev-esccay-favicon.svg');
    }
}
