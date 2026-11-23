<?php

namespace App\Services;

use App\Support\GhanaPhone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Send SMS and OTP via SMS Online Ghana API.
 * @see https://api.smsonlinegh.com/v5/message/sms/send
 */
final class SmsOnlineService
{
    private const BASE_URL = 'https://api.smsonlinegh.com/v5/message/sms/send';
    private const OTP_CACHE_PREFIX = 'phone_otp:';
    private const OTP_TTL_SECONDS = 600; // 10 minutes
    private const OTP_LENGTH = 6;
    private const TYPE_GSM = 0;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $senderId
    ) {}

    public static function fromConfig(): self
    {
        $apiKey = (string) SettingsService::get('sms_api_key', config('services.sms.api_key', ''));
        $senderId = (string) SettingsService::get('sms_sender_id', config('services.sms.sender_id', ''));
        return new self($apiKey, $senderId ?: config('app.name', 'R2BMarket'));
    }

    /**
     * Generate OTP, store in cache, send via SMS Online, return result.
     */
    public function sendPhoneVerificationOtp(string $phone): array
    {
        $normalized = GhanaPhone::normalize($phone);
        if ($normalized === null) {
            return ['success' => false, 'message' => 'Invalid Ghana phone number.'];
        }

        $otp = $this->generateOtp();
        $cacheKey = self::OTP_CACHE_PREFIX . $normalized;
        Cache::put($cacheKey, $otp, self::OTP_TTL_SECONDS);

        $siteName = SettingsService::get('site_name') ?: config('app.name', 'R2BMarket');
        $message = "Your {$siteName} verification code is: {$otp}. Valid for 10 minutes.";
        $result = $this->sendQuickSms([$normalized], $message, true);

        if (!$result['success']) {
            Cache::forget($cacheKey);
            return $result;
        }

        return ['success' => true, 'message' => 'Verification code sent.'];
    }

    /**
     * Verify OTP for the given phone. Returns true if valid; forgets the OTP on success.
     */
    public function verifyPhoneOtp(string $phone, string $otp): bool
    {
        $normalized = GhanaPhone::normalize($phone);
        if ($normalized === null) {
            return false;
        }
        $cacheKey = self::OTP_CACHE_PREFIX . $normalized;
        $stored = Cache::get($cacheKey);
        if ($stored === null || !hash_equals((string) $stored, (string) $otp)) {
            return false;
        }
        Cache::forget($cacheKey);
        return true;
    }

    /**
     * Send SMS to one or more recipients.
     * Recipients can be 0XXXXXXXXX (Ghana local) or 233XXXXXXXXX; they are passed as-is to the API.
     */
    public function sendQuickSms(array $recipients, string $message, bool $isOtp = false): array
    {
        if ($this->apiKey === '') {
            Log::warning('SMS Online API key not set. Skipping SMS.');
            return ['success' => false, 'message' => 'SMS not configured.'];
        }

        $destinations = array_values(array_map(function ($r) {
            $n = GhanaPhone::normalize($r);
            if ($n !== null) {
                return $n; // 0XXXXXXXXX
            }
            $digits = preg_replace('/\D/', '', $r);
            if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
                return $digits;
            }
            if (strlen($digits) === 12 && str_starts_with($digits, '233')) {
                return $digits;
            }
            return $r;
        }, $recipients));

        if (empty($destinations)) {
            return ['success' => false, 'message' => 'No valid destinations.'];
        }

        $payload = [
            'text' => $message,
            'type' => self::TYPE_GSM,
            'sender' => $this->senderId ?: config('app.name', 'R2BMarket'),
            'destinations' => $destinations,
        ];

        $response = Http::timeout(15)
            ->withHeaders([
                'Host' => 'api.smsonlinegh.com',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'key ' . trim($this->apiKey),
            ])
            ->post(self::BASE_URL, $payload);

        $body = $response->json();
        $ok = $response->successful();

        if (!$ok) {
            Log::warning('SMS Online send failed.', [
                'status' => $response->status(),
                'body' => $body,
            ]);
            $message = 'Failed to send SMS. Try again.';
            if (is_array($body)) {
                $handshake = $body['handshake'] ?? null;
                if (is_array($handshake) && isset($handshake['label'])) {
                    if (str_contains((string) $handshake['label'], 'AUTH')) {
                        $message = 'Invalid SMS API key. Check SMS_API_KEY in .env and your SMS Online account.';
                    }
                }
            }
            if ($response->status() === 401) {
                $message = 'Invalid SMS API key. Please check SMS_API_KEY in .env.';
            }
            return ['success' => false, 'message' => $message];
        }

        return ['success' => true, 'message' => $body['message'] ?? 'Sent.'];
    }

    private function generateOtp(): string
    {
        $digits = '';
        for ($i = 0; $i < self::OTP_LENGTH; $i++) {
            $digits .= (string) random_int(0, 9);
        }
        return $digits;
    }
}
