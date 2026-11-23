<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $authUser = null;
        $adminUser = null;
        $unreadCount = 0;
        $recentNotifications = [];

        try {
            $user = auth('jwt')->user() ?? auth('web')->user();
            if ($user) {
                $balance = $user->balance ?? 0;
                $authUser = [
                    'id' => (int) $user->id,
                    'name' => (string) ($user->name ?? ''),
                    'email' => (string) ($user->email ?? ''),
                    'phone' => ($user->phone !== null && $user->phone !== '') ? (string) $user->phone : null,
                    'balance' => is_scalar($balance) ? (string) $balance : '0',
                    'is_admin' => (bool) ($user->is_admin ?? false),
                ];
                $webUser = $request->user();
                if ($webUser && !empty($webUser->is_admin)) {
                    $adminUser = [
                        'name' => (string) ($webUser->name ?? ''),
                        'email' => (string) ($webUser->email ?? ''),
                    ];
                }
                try {
                    if (Schema::hasTable('notifications')) {
                        $unreadCount = $user->unreadNotifications()->count();
                        $recentNotifications = $user->notifications()
                            ->orderByDesc('created_at')
                            ->limit(8)
                            ->get()
                            ->map(function ($n) {
                                $data = $n->data ?? [];
                                return [
                                    'id' => $n->id,
                                    'title' => $data['title'] ?? 'Notification',
                                    'message' => $data['message'] ?? '',
                                    'type' => $data['type'] ?? 'info',
                                    'url' => $data['url'] ?? null,
                                    'read_at' => $n->read_at?->toIso8601String(),
                                    'created_at' => $n->created_at->toIso8601String(),
                                ];
                            })
                            ->all();
                    }
                } catch (\Throwable $e) {
                    // ignore notifications
                }
            }
        } catch (\Throwable $e) {
            // Auth or DB failed – send empty auth so site still loads
        }

        $supportWhatsAppUrl = function_exists('settings') ? (settings('support_whatsapp_url') ?: '') : '';
        $supportWhatsAppGroupUrl = function_exists('settings') ? (settings('support_whatsapp_group_url') ?: '') : '';
        $siteName = function_exists('settings') ? (settings('site_name') ?: config('app.name')) : config('app.name');
        $contactEmail = function_exists('settings') ? (settings('contact_email') ?: '') : '';
        $contactPhone = function_exists('settings') ? (settings('contact_phone') ?: '') : '';
        $manualTopupEnabled = false;
        $manualPaymentBankName = '';
        $manualPaymentAccountNumber = '';
        $manualPaymentMobileMoney = '';
        $manualPaymentInstructions = '';
        try {
            if (function_exists('settings')) {
                $manualTopupEnabled = SettingsService::getBool('manual_topup_enabled');
                $manualPaymentBankName = (string) (settings('manual_payment_bank_name') ?: '');
                $manualPaymentAccountNumber = (string) (settings('manual_payment_account_number') ?: '');
                $manualPaymentMobileMoney = (string) (settings('manual_payment_mobile_money') ?: '');
                $manualPaymentInstructions = (string) (settings('manual_payment_instructions') ?: '');
            }
        } catch (\Throwable $e) {
            // keep defaults
        }

        try {
            return [
                ...parent::share($request),
                'auth' => [
                    'user' => $authUser,
                    'admin' => $adminUser,
                ],
                'notifications' => [
                    'unread_count' => $unreadCount,
                    'recent' => $recentNotifications,
                ],
                'flash' => [
                    'success' => fn () => $request->session()->get('success'),
                    'error' => fn () => $request->session()->get('error'),
                ],
                'support_whatsapp_url' => $supportWhatsAppUrl,
                'support_whatsapp_group_url' => $supportWhatsAppGroupUrl,
                'site_name' => $siteName,
                'contact_email' => $contactEmail,
                'contact_phone' => $contactPhone,
                'manual_topup_enabled' => $manualTopupEnabled,
                'manual_payment_bank_name' => $manualPaymentBankName,
                'manual_payment_account_number' => $manualPaymentAccountNumber,
                'manual_payment_mobile_money' => $manualPaymentMobileMoney,
                'manual_payment_instructions' => $manualPaymentInstructions,
            ];
        } catch (\Throwable $e) {
            return [
                'auth' => ['user' => $authUser, 'admin' => $adminUser],
                'notifications' => ['unread_count' => $unreadCount, 'recent' => $recentNotifications],
                'flash' => ['success' => fn () => null, 'error' => fn () => null],
                'support_whatsapp_url' => $supportWhatsAppUrl,
                'support_whatsapp_group_url' => $supportWhatsAppGroupUrl,
                'site_name' => $siteName,
                'contact_email' => $contactEmail,
                'contact_phone' => $contactPhone,
                'manual_topup_enabled' => $manualTopupEnabled,
                'manual_payment_bank_name' => $manualPaymentBankName,
                'manual_payment_account_number' => $manualPaymentAccountNumber,
                'manual_payment_mobile_money' => $manualPaymentMobileMoney,
                'manual_payment_instructions' => $manualPaymentInstructions,
            ];
        }
    }
}
