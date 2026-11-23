<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Paystack payment gateway: initialize transaction and verify.
 * @see https://paystack.com/docs/payments/accept-payments
 */
final class PaystackService
{
    private const BASE_URL = 'https://api.paystack.co';

    private function getSecretKey(): string
    {
        return trim((string) SettingsService::get('paystack_secret_key', config('services.paystack.secret_key', '')));
    }

    /**
     * Initialize a transaction. Amount in GHS (will be sent as pesewas to Paystack).
     *
     * @param string $email Customer email
     * @param float $amountGhs Amount in Ghana Cedis
     * @param string $reference Unique reference (e.g. TUP-xxx)
     * @param string $callbackUrl URL Paystack redirects to after payment
     * @param array $metadata Optional metadata (e.g. user_id)
     * @return array{success: bool, authorization_url?: string, reference?: string, message?: string}
     */
    public function initializeTransaction(
        string $email,
        float $amountGhs,
        string $reference,
        string $callbackUrl,
        array $metadata = []
    ): array {
        $key = $this->getSecretKey();
        if ($key === '') {
            Log::warning('Paystack secret key not set.');
            return ['success' => false, 'message' => 'Paystack is not configured.'];
        }

        // Paystack amount is in pesewas (1 GHS = 100 pesewas)
        $amountPesewas = (int) round($amountGhs * 100);
        if ($amountPesewas < 100) {
            return ['success' => false, 'message' => 'Minimum amount is GH₵1.00.'];
        }

        $response = Http::withToken($key)
            ->acceptJson()
            ->post(self::BASE_URL . '/transaction/initialize', [
                'email' => $email,
                'amount' => $amountPesewas,
                'reference' => $reference,
                'callback_url' => $callbackUrl,
                'metadata' => $metadata,
                'currency' => 'GHS',
            ]);

        $body = $response->json();
        if (!$response->successful()) {
            Log::warning('Paystack initialize failed', ['status' => $response->status(), 'body' => $body]);
            return [
                'success' => false,
                'message' => $body['message'] ?? 'Could not start payment. Please try again.',
            ];
        }

        $data = $body['data'] ?? [];
        $authUrl = $data['authorization_url'] ?? null;
        if (!$authUrl) {
            return ['success' => false, 'message' => 'Invalid response from payment provider.'];
        }

        return [
            'success' => true,
            'authorization_url' => $authUrl,
            'access_code' => $data['access_code'] ?? null,
            'reference' => $data['reference'] ?? $reference,
        ];
    }

    /**
     * Verify a transaction by reference.
     *
     * @return array{success: bool, verified: bool, amount?: float, message?: string}
     */
    public function verifyTransaction(string $reference): array
    {
        $key = $this->getSecretKey();
        if ($key === '') {
            return ['success' => false, 'verified' => false, 'message' => 'Paystack is not configured.'];
        }

        $ref = trim($reference);
        if ($ref === '') {
            return ['success' => false, 'verified' => false, 'message' => 'Reference is required.'];
        }

        $response = Http::withToken($key)
            ->acceptJson()
            ->get(self::BASE_URL . '/transaction/verify/' . rawurlencode($ref));

        $body = $response->json();
        if (!$response->successful()) {
            Log::warning('Paystack verify failed', ['status' => $response->status(), 'body' => $body]);
            return [
                'success' => false,
                'verified' => false,
                'message' => $body['message'] ?? 'Verification failed.',
            ];
        }

        $data = $body['data'] ?? [];
        $status = strtolower((string) ($data['status'] ?? ''));
        $amountPaid = isset($data['amount']) ? (float) $data['amount'] / 100 : 0; // pesewas to GHS

        return [
            'success' => true,
            'verified' => $status === 'success',
            'amount' => $amountPaid,
            'status' => $status,
            'reference' => $data['reference'] ?? $ref,
        ];
    }

    /**
     * Verify Paystack webhook signature (X-Paystack-Signature).
     * Signature is HMAC SHA512 of the raw request body with the secret key.
     *
     * @param  string  $payload  Raw request body
     * @param  string  $signature  X-Paystack-Signature header value
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $key = $this->getSecretKey();
        if ($key === '') {
            return false;
        }
        $computed = hash_hmac('sha512', $payload, $key);

        return hash_equals($computed, $signature);
    }
}
