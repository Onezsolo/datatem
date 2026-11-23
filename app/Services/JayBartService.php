<?php

namespace App\Services;

use App\Models\DataPackage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * JayBart data bundles API.
 * @see https://agent.jaybartservices.com/api/v1
 */
final class JayBartService
{
    public static function getBaseUrl(): string
    {
        $url = SettingsService::get('jaybart_base_url', config('services.jaybart.base_url', 'https://agent.jaybartservices.com/api/v1'));
        return rtrim((string) $url, '/');
    }

    /**
     * API key from SettingsService (admin) or config (.env).
     */
    public static function getApiKey(): string
    {
        $key = SettingsService::get('jaybart_api_key', config('services.jaybart.api_key', ''));
        return trim((string) $key);
    }

    /**
     * Check console (agent) balance.
     * @return array{success: bool, balance?: string, message?: string}
     */
    public static function checkConsoleBalance(): array
    {
        $key = self::getApiKey();
        if ($key === '') {
            return ['success' => false, 'message' => 'JayBart API key not set.'];
        }

        $response = Http::timeout(15)
            ->withHeaders(['x-api-key' => $key])
            ->get(self::getBaseUrl() . '/check-console-balance');

        $body = $response->json();
        if (!$response->successful()) {
            Log::warning('JayBart check-console-balance failed', ['status' => $response->status(), 'body' => $body]);
            return [
                'success' => false,
                'message' => $body['message'] ?? 'Failed to fetch balance.',
            ];
        }

        $balance = $body['userWalletBalance'] ?? null;
        return [
            'success' => true,
            'balance' => $balance !== null ? (string) $balance : '0',
            'message' => $body['message'] ?? null,
        ];
    }

    /**
     * Fetch data packages from API.
     * @return array{list: array<int, array>, error: string|null, message: string|null}
     */
    public static function fetchDataPackages(): array
    {
        $key = self::getApiKey();
        if ($key === '') {
            Log::warning('JayBart API key not set. Set JAYBART_API_KEY in .env and run: php artisan config:clear');
            return [
                'list' => [],
                'error' => 'api_key_not_set',
                'message' => 'JAYBART_API_KEY is not set in .env. Add it and run: php artisan config:clear',
            ];
        }

        $url = self::getBaseUrl() . '/fetch-data-packages';
        $response = Http::timeout(30)
            ->withHeaders(['x-api-key' => $key])
            ->get($url);

        if (!$response->successful()) {
            $body = $response->json();
            Log::warning('JayBart fetch-data-packages failed', ['status' => $response->status(), 'body' => $body, 'url' => $url]);
            $apiMessage = is_array($body) ? ($body['message'] ?? $body['error'] ?? null) : null;
            $message = $apiMessage ?: 'API returned ' . $response->status() . '. Check JAYBART_API_KEY and network.';
            return [
                'list' => [],
                'error' => 'api_error',
                'message' => $message,
            ];
        }

        $list = $response->json();
        return [
            'list' => is_array($list) ? $list : [],
            'error' => null,
            'message' => null,
        ];
    }

    /**
     * Sync packages from API to database. Upserts by external_id; preserves existing selling_price.
     * Returns error/message when API key is missing or API request fails.
     */
    public static function syncPackagesToDb(): array
    {
        $fetch = self::fetchDataPackages();
        $list = $fetch['list'] ?? [];
        if (isset($fetch['error']) && $fetch['error'] !== null) {
            return [
                'created' => 0,
                'updated' => 0,
                'total' => 0,
                'error' => $fetch['error'],
                'message' => $fetch['message'] ?? 'Sync failed.',
            ];
        }

        $created = 0;
        $updated = 0;

        foreach ($list as $item) {
            $externalId = isset($item['id']) ? (string) $item['id'] : '0';
            if ($externalId === '0' || $externalId === '') {
                continue;
            }
            $consolePrice = (float) (preg_replace('/[^\d.]/', '', $item['console_price'] ?? '0') ?: 0);
            $existing = DataPackage::where('external_id', $externalId)->first();
            if ($existing) {
                $existing->update([
                    'network_id' => (int) ($item['network_id'] ?? 0),
                    'network' => (string) ($item['network'] ?? ''),
                    'volume' => (float) ($item['volume'] ?? 0),
                    'volume_gb' => (string) ($item['volumeGB'] ?? ''),
                    'console_price' => $consolePrice,
                    'status' => (string) ($item['status'] ?? 'In Stock'),
                ]);
                $updated++;
            } else {
                DataPackage::create([
                    'external_id' => $externalId,
                    'network_id' => (int) ($item['network_id'] ?? 0),
                    'network' => (string) ($item['network'] ?? ''),
                    'volume' => (float) ($item['volume'] ?? 0),
                    'volume_gb' => (string) ($item['volumeGB'] ?? ''),
                    'console_price' => $consolePrice,
                    'selling_price' => $consolePrice,
                    'status' => (string) ($item['status'] ?? 'In Stock'),
                ]);
                $created++;
            }
        }

        return ['created' => $created, 'updated' => $updated, 'total' => count($list)];
    }

    /**
     * Fetch networks (fetch-networks).
     * @return array<int, array{id: int, name: string, description: string}>
     */
    public static function fetchNetworks(): array
    {
        $key = self::getApiKey();
        if ($key === '') {
            return [];
        }

        $response = Http::timeout(15)
            ->withHeaders(['x-api-key' => $key])
            ->get(self::getBaseUrl() . '/fetch-networks');

        if (!$response->successful()) {
            Log::debug('JayBart fetch-networks failed', ['status' => $response->status(), 'body' => $response->json()]);
            return [];
        }

        $list = $response->json();
        return is_array($list) ? $list : [];
    }

    /**
     * Purchase a data package (buy-other-package).
     * API expects: recipient_msisdn, network_id, shared_bundle (no package_id).
     * @param string $recipientMsisdn Ghana number e.g. 0551053716
     * @param int $networkId JayBart network id (1=AT iSHare, 2=TELECEL, 3=MTN, 4=AT BigTime, 5=MTN AFA)
     * @param int $sharedBundle Volume in API units (e.g. 10000 for 10GB – from fetch-data-packages volume or volume_gb)
     * @return array{success: bool, message?: string, transaction_code?: string}
     */
    public static function buyPackage(string $recipientMsisdn, int $networkId, int $sharedBundle): array
    {
        $key = self::getApiKey();
        if ($key === '') {
            return ['success' => false, 'message' => 'JayBart API key not set.'];
        }

        if ($networkId < 1 || $sharedBundle < 1) {
            return ['success' => false, 'message' => 'Network and bundle size are required.'];
        }

        $msisdn = preg_replace('/\s+/', '', $recipientMsisdn);
        $payload = [
            'recipient_msisdn' => $msisdn,
            'network_id' => $networkId,
            'shared_bundle' => $sharedBundle,
        ];

        $url = self::getBaseUrl() . '/buy-other-package';
        $attempts = 3;
        $response = null;
        for ($i = 0; $i < $attempts; $i++) {
            $response = Http::timeout(30)
                ->acceptJson()
                ->withHeaders(['x-api-key' => $key])
                ->post($url, $payload);
            if ($response->successful()) {
                break;
            }
            if (! in_array($response->status(), [429, 502], true) || $i === $attempts - 1) {
                break;
            }
            usleep(2000000); // 2 seconds before retry
        }

        $body = $response->json();
        if (!$response->successful()) {
            Log::warning('JayBart buy-other-package failed', [
                'status' => $response->status(),
                'body' => $body,
                'recipient' => $msisdn,
                'network_id' => $networkId,
                'shared_bundle' => $sharedBundle,
            ]);
            $status = $response->status();
            $apiMessage = $body['message'] ?? 'Purchase failed.';
            if ($status === 409) {
                $apiMessage = 'This bundle for this number was already ordered. You can try again after 5 minutes.';
            } elseif ($status === 429) {
                $apiMessage = 'Too many requests. Please try again in a moment.';
            } elseif ($status === 502) {
                $apiMessage = 'Temporary provider error. Please try again in a moment.';
            }
            $existingTransactionCode = $body['transaction_code'] ?? $body['transaction_id'] ?? $body['reference'] ?? null;
            if ($existingTransactionCode !== null && $existingTransactionCode !== '') {
                $existingTransactionCode = (string) $existingTransactionCode;
            } else {
                $existingTransactionCode = null;
            }
            return [
                'success' => false,
                'message' => $apiMessage,
                'transaction_code' => $existingTransactionCode,
            ];
        }

        $success = (bool) ($body['success'] ?? false);
        $transactionCode = $body['transaction_code'] ?? null;
        $message = $body['message'] ?? ($success ? 'Package purchased successfully.' : 'Purchase failed.');
        if (!$success && $message !== '') {
            $msgLower = strtolower($message);
            if (str_contains($msgLower, 'duplicate') || str_contains($msgLower, 'already')) {
                $message = 'This bundle for this number was already ordered. You can try again after 5 minutes.';
            }
        }
        return [
            'success' => $success,
            'message' => $message,
            'transaction_code' => $transactionCode ? (string) $transactionCode : null,
        ];
    }

    /**
     * Fetch transaction status (fetch-other-network-transaction).
     * @param string $transactionId e.g. txn_9aff1d90-ff4d-479c-9c7f-7b27b05be2a2
     * @return array{success: bool, order_items?: array<int, array{status: string, beneficiary_number?: string}>, message?: string}
     */
    public static function fetchTransactionStatus(string $transactionId): array
    {
        $key = self::getApiKey();
        if ($key === '') {
            return ['success' => false, 'message' => 'JayBart API key not set.'];
        }

        $tid = trim($transactionId);
        if ($tid === '') {
            return ['success' => false, 'message' => 'Transaction ID required.'];
        }

        $url = self::getBaseUrl() . '/fetch-other-network-transaction';
        $attempts = 3;
        $response = null;
        for ($i = 0; $i < $attempts; $i++) {
            $response = Http::timeout(20)
                ->acceptJson()
                ->withHeaders(['x-api-key' => $key])
                ->post($url, ['transaction_id' => $tid]);
            if ($response->successful()) {
                break;
            }
            if (! in_array($response->status(), [429, 502], true) || $i === $attempts - 1) {
                break;
            }
            usleep(2000000);
        }

        $body = $response->json();
        if (! $response->successful()) {
            Log::debug('JayBart fetch-other-network-transaction failed', [
                'status' => $response->status(),
                'body' => $body,
                'transaction_id' => $tid,
            ]);
            return [
                'success' => false,
                'message' => $body['message'] ?? 'Failed to fetch transaction status.',
            ];
        }

        $orderItems = $body['order_items'] ?? [];
        if (!is_array($orderItems)) {
            $orderItems = [];
        }

        return [
            'success' => true,
            'order_items' => $orderItems,
        ];
    }

    /**
     * Map API order item status to our purchase status.
     * We never use API amount; our price is always from purchases.amount.
     */
    public static function mapApiStatusToOurStatus(string $apiStatus): string
    {
        $s = strtoupper(trim($apiStatus));
        if (in_array($s, ['DELIVERED', 'SUCCESS', 'COMPLETED', 'SUCCESSFUL'], true)) {
            return 'delivered';
        }
        if (in_array($s, ['FAILED', 'REJECTED', 'CANCELLED'], true)) {
            return 'failed';
        }
        return 'processing';
    }
}
