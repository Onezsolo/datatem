<?php

namespace App\Services;

use App\Models\DataPackage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Legacy data API – balance, bundles, and orders for the reseller storefront.
 */
final class LegacyDataService
{
    public static function getBaseUrl(): string
    {
        $url = SettingsService::get('legacy_data_base_url', config('services.legacy_data.base_url', ''));
        return rtrim((string) $url, '/');
    }

    public static function getApiKey(): string
    {
        $key = SettingsService::get('legacy_data_api_key', config('services.legacy_data.api_key', ''));
        return trim((string) $key);
    }

    /**
     * Check account balance (GET /balance).
     * @return array{success: bool, balance?: string, message?: string}
     */
    public static function checkConsoleBalance(): array
    {
        $key = self::getApiKey();
        if ($key === '') {
            return ['success' => false, 'message' => 'Storefront data API key not set.'];
        }

        $response = Http::timeout(15)
            ->withHeaders([
                'X-API-KEY' => $key,
                'Content-Type' => 'application/json',
            ])
            ->get(self::getBaseUrl() . '/balance');

        $data = $response->json();
        $rawBody = $response->body();
        if (! $response->successful()) {
            $logBody = $data ?? (strlen($rawBody) > 300 ? substr($rawBody, 0, 300) . '...' : $rawBody);
            Log::warning('Legacy data balance failed', ['status' => $response->status(), 'body' => $logBody]);
            $message = is_array($data) && isset($data['data']['message'])
                ? (string) $data['data']['message']
                : 'Failed to fetch balance (HTTP ' . $response->status() . ').';
            return [
                'success' => false,
                'message' => $message,
            ];
        }

        if (! is_array($data) || ($data['status'] ?? '') !== 'success') {
            return [
                'success' => false,
                'message' => $data['data']['message'] ?? 'Invalid response.',
            ];
        }

        $balance = $data['data']['balance'] ?? null;
        return [
            'success' => true,
            'balance' => $balance !== null ? (string) $balance : '0',
            'message' => null,
        ];
    }

    /**
     * Fetch data packages. Single GET to base_url/bundles.php (no ?network=) – same as Postman.
     * Returns exactly what the API returns (e.g. MTN 1 GB at 4.50, 21 bundles).
     * @return array{list: array<int, array>, error: string|null, message: string|null}
     */
    public static function fetchDataPackages(): array
    {
        $key = self::getApiKey();
        if ($key === '') {
            Log::warning('Storefront data API key not set.');
            return [
                'list' => [],
                'error' => 'api_key_not_set',
                'message' => 'Storefront data API key is not set. Add it in Admin → Settings.',
            ];
        }

        // Use .env base URL so we match Postman (Admin → Settings can override other calls with a different URL)
        $baseUrl = rtrim((string) config('services.legacy_data.base_url', ''), '/');
        $bundlesPath = config('services.legacy_data.bundles_path', 'bundles.php');

        // Same as Postman: GET base_url/bundles.php (no query param)
        $url = $baseUrl . '/' . ltrim($bundlesPath, '/');
        Log::info('Legacy data bundles request', ['url' => $url]);

        $response = Http::timeout(30)
            ->withHeaders([
                'X-API-KEY' => $key,
                'Content-Type' => 'application/json',
            ])
            ->get($url);

        $body = $response->json();
        $rawBody = $response->body();
        if (! $response->successful()) {
            $logBody = $body ?? (strlen($rawBody) > 300 ? substr($rawBody, 0, 300) . '...' : $rawBody);
            Log::warning('Legacy data bundles failed', ['url' => $url, 'status' => $response->status(), 'body' => $logBody]);
            return [
                'list' => [],
                'error' => 'fetch_failed',
                'message' => 'Failed to fetch bundles. Check LEGACY_DATA_BASE_URL and API key.',
            ];
        }

        if (! is_array($body) || ($body['status'] ?? '') !== 'success') {
            return [
                'list' => [],
                'error' => 'invalid_response',
                'message' => $body['data']['message'] ?? 'Invalid API response.',
            ];
        }

        $bundles = $body['data']['bundles'] ?? [];
        $allBundles = is_array($bundles) ? $bundles : [];

        if (count($allBundles) > 0) {
            Log::info('Legacy data bundles response sample', ['url' => $url, 'first_bundle' => $allBundles[0], 'total_fetched' => count($allBundles)]);
            // Log MTN 1 GB from API response so we can confirm price (4.50 vs 54.50)
            $mtn1gb = collect($allBundles)->first(function ($item) {
                $net = trim((string) ($item['network'] ?? ''));
                $plan = trim((string) ($item['plan_name'] ?? ''));
                return $net === 'MTN' && (strcasecmp($plan, '1 GB') === 0 || strcasecmp($plan, '1GB') === 0);
            });
            if ($mtn1gb !== null) {
                Log::info('Legacy data MTN 1 GB from API (raw)', ['network' => $mtn1gb['network'] ?? '', 'plan_name' => $mtn1gb['plan_name'] ?? '', 'price' => $mtn1gb['price'] ?? '', 'bundle_id' => $mtn1gb['bundle_id'] ?? '']);
            }
        }

        $list = [];
        foreach ($allBundles as $b) {
            $network = (string) ($b['network'] ?? '');
            $planName = (string) ($b['plan_name'] ?? '');
            $price = (float) (preg_replace('/[^\d.]/', '', (string) ($b['price'] ?? '0')) ?: 0);
            // Include price in identity so 1GB at 5.40 and 1GB at 54.50 are separate packages
            $externalId = abs(crc32($network . '|' . $planName . '|' . $price));
            if ($externalId === 0) {
                $externalId = count($list) + 1;
            }
            $volume = self::parseVolumeFromPlanName($planName);
            $networkId = self::networkNameToId($network);
            // Prefer bundle_id (legacy data API) over id
            $providerBundleId = isset($b['bundle_id']) ? (string) $b['bundle_id'] : (isset($b['id']) ? (string) $b['id'] : (isset($b['plan_id']) ? (string) $b['plan_id'] : (isset($b['code']) ? (string) $b['code'] : null)));
            $list[] = [
                'id' => $externalId,
                'network_id' => $networkId,
                'network' => $network,
                'volume' => $volume,
                'volumeGB' => $planName,
                'console_price' => $price,
                'status' => 'In Stock',
                'providerBundleId' => $providerBundleId,
            ];
        }

        // Sort by network then volume (1 GB, 2 GB, 3 GB …) so display order is correct
        usort($list, function ($a, $b) {
            $cmp = strcmp($a['network'] ?? '', $b['network'] ?? '');
            if ($cmp !== 0) {
                return $cmp;
            }
            $vA = (float) ($a['volume'] ?? 0);
            $vB = (float) ($b['volume'] ?? 0);
            return $vA <=> $vB;
        });

        // Log a few samples so we can confirm plan_name vs price and whether bundle id exists
        $samples = array_slice($list, 0, 5);
        Log::info('Legacy data sync: sample bundles stored', array_map(function ($item) {
            return [
                'network' => $item['network'],
                'plan_name_stored_as_volume_gb' => $item['volumeGB'],
                'console_price' => $item['console_price'],
                'provider_bundle_id' => $item['providerBundleId'] ?? null,
            ];
        }, $samples));

        // Log every ~1GB bundle from API so we can see which network has which price (e.g. 5.40 vs 64.50)
        $oneGbBundles = array_filter($list, function ($item) {
            $v = (float) ($item['volume'] ?? 0);
            return $v >= 0.9 && $v <= 1.1;
        });
        if (count($oneGbBundles) > 0) {
            Log::info('Legacy data sync: all 1GB bundles from API (network, plan, price, bundle_id)', array_values(array_map(function ($item) {
                return [
                    'network' => $item['network'],
                    'plan_name' => $item['volumeGB'],
                    'console_price' => $item['console_price'],
                    'provider_bundle_id' => $item['providerBundleId'] ?? null,
                ];
            }, $oneGbBundles)));
        }

        return [
            'list' => $list,
            'error' => null,
            'message' => null,
        ];
    }

    /**
     * Sync packages from API to database. Same structure as before: upsert by external_id.
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

        Log::info('Legacy data sync: starting DB upsert', ['list_count' => count($list)]);

        $created = 0;
        $updated = 0;

        foreach ($list as $item) {
            $externalId = isset($item['id']) ? (string) $item['id'] : '0';
            if ($externalId === '0' || $externalId === '') {
                continue;
            }
            $consolePrice = (float) ($item['console_price'] ?? 0);
            $providerBundleId = isset($item['providerBundleId']) && $item['providerBundleId'] !== '' ? (string) $item['providerBundleId'] : null;

            // Prefer lookup by provider_bundle_id so API bundle (e.g. MTN 1 GB 4.50 = 162) is always preserved
            $existing = null;
            if ($providerBundleId !== null && $providerBundleId !== '') {
                $existing = DataPackage::where('provider_bundle_id', $providerBundleId)->first();
            }
            if ($existing === null) {
                $existing = DataPackage::where('external_id', $externalId)->first();
            }

            if ($existing) {
                $existing->update([
                    'external_id' => $externalId,
                    'network_id' => (int) ($item['network_id'] ?? 0),
                    'network' => (string) ($item['network'] ?? ''),
                    'volume' => (float) ($item['volume'] ?? 0),
                    'volume_gb' => (string) ($item['volumeGB'] ?? ''),
                    'console_price' => $consolePrice,
                    'status' => (string) ($item['status'] ?? 'In Stock'),
                    'provider_bundle_id' => $providerBundleId,
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
                    'provider_bundle_id' => $providerBundleId,
                ]);
                $created++;
            }
        }

        return ['created' => $created, 'updated' => $updated, 'total' => count($list)];
    }

    /**
     * Create order (POST /order.php). API expects: network, data_plan, beneficiary.
     * When $dataPlanOverride is provided, that exact plan string is sent (trim only) so the provider matches the correct bundle/price.
     * When $providerBundleId is provided (from API bundle id/bundle_id), it is sent as bundle_id if the provider supports it.
     * When $networkNameOverride is provided (e.g. "Express(MTN)"), that exact network string is sent so the provider matches the same network as in bundles.
     */
    public static function buyPackage(string $recipientMsisdn, int $networkId, int $sharedBundle, ?string $dataPlanOverride = null, ?string $providerBundleId = null, ?string $networkNameOverride = null): array
    {
        $key = self::getApiKey();
        if ($key === '') {
            return ['success' => false, 'message' => 'Storefront data API key not set.'];
        }

        $beneficiary = preg_replace('/\s+/', '', $recipientMsisdn);
        $network = ($networkNameOverride !== null && trim($networkNameOverride) !== '')
            ? trim($networkNameOverride)
            : self::networkIdToName($networkId);
        // When override is provided, send the exact plan string from the API (trim + collapse spaces) so the provider matches the correct bundle/price
        $dataPlan = $dataPlanOverride !== null && trim($dataPlanOverride) !== ''
            ? trim($dataPlanOverride)
            : self::sharedBundleToDataPlan($sharedBundle);
        $dataPlan = preg_replace('/\s+/', ' ', trim((string) $dataPlan));

        if ($network === '' || $dataPlan === '') {
            return ['success' => false, 'message' => 'Network and data plan are required.'];
        }

        $payload = [
            'network' => $network,
            'data_plan' => $dataPlan,
            'beneficiary' => $beneficiary,
        ];
        if ($providerBundleId !== null && trim($providerBundleId) !== '') {
            $payload['bundle_id'] = trim($providerBundleId);
        }

        $baseUrl = self::getBaseUrl();
        $url = rtrim($baseUrl, '/') . '/order.php';
        $response = Http::timeout(30)
            ->acceptJson()
            ->withHeaders([
                'X-API-KEY' => $key,
                'Content-Type' => 'application/json',
            ])
            ->post($url, $payload);

        $body = $response->json();
        $rawBody = $response->body();

        if (! $response->successful()) {
            $logBody = $body;
            if ($logBody === null && $rawBody !== '') {
                $logBody = strlen($rawBody) > 500 ? substr($rawBody, 0, 500) . '...' : $rawBody;
            }
            Log::warning('Legacy data order failed', [
                'status' => $response->status(),
                'body' => $logBody,
                'body_empty' => $rawBody === '',
                'url_base' => $baseUrl,
                'payload' => $payload,
            ]);
            $message = self::buildOrderErrorMessage($response->status(), $body);
            return [
                'success' => false,
                'message' => $message,
                'transaction_code' => is_array($body) ? ($body['data']['reference'] ?? null) : null,
            ];
        }

        if (! is_array($body) || ($body['status'] ?? '') !== 'success') {
            $msg = is_array($body) && isset($body['data']['message'])
                ? (string) $body['data']['message']
                : 'Order failed. Invalid response from provider.';
            return [
                'success' => false,
                'message' => $msg,
                'transaction_code' => null,
            ];
        }

        $reference = $body['data']['reference'] ?? null;
        $apiStatus = $body['data']['status'] ?? null;

        return [
            'success' => true,
            'message' => 'Order created successfully.',
            'transaction_code' => $reference ? (string) $reference : null,
            'order_status' => $apiStatus ? (string) $apiStatus : null,
        ];
    }

    /**
     * Build a clear error message for failed order API response.
     * When the provider returns "Insufficient balance", clarify it's their account balance (required vs available).
     */
    private static function buildOrderErrorMessage(int $status, $body): string
    {
        if (! is_array($body) || ! isset($body['data']['message'])) {
            return 'Order failed. The provider returned an error (HTTP ' . $status . '). Please try again or contact support.';
        }
        $apiMessage = (string) $body['data']['message'];
        $code = isset($body['data']['code']) ? (string) $body['data']['code'] : '';
        $details = $body['data']['details'] ?? null;
        if (($code === 'INSUFFICIENT_BALANCE' || stripos($apiMessage, 'insufficient balance') !== false) && is_array($details)) {
            $required = isset($details['required']) ? (string) $details['required'] : '';
            $available = isset($details['available']) ? (string) $details['available'] : '';
            if ($required !== '' || $available !== '') {
                return 'Insufficient balance on your storefront data provider account. Required: GH₵ ' . $required . ', Available: GH₵ ' . $available . '. Top up that account and try again.';
            }
        }
        return $apiMessage;
    }

    /**
     * Fetch transaction/order status from the API (e.g. GET order-status.php?reference=XXX).
     * Returns order_items so admin "Check processing orders status" can update delivered/failed.
     * @return array{success: bool, order_items?: array, message?: string}
     */
    public static function fetchTransactionStatus(string $transactionId): array
    {
        $key = self::getApiKey();
        if ($key === '') {
            return ['success' => false, 'message' => 'Storefront data API key not set.'];
        }

        $tid = trim($transactionId);
        if ($tid === '') {
            return ['success' => false, 'message' => 'Transaction ID required.'];
        }

        $baseUrl = self::getBaseUrl();
        $statusPath = config('services.legacy_data.order_status_path', 'order-status.php');
        $url = rtrim($baseUrl, '/') . '/' . ltrim($statusPath, '/') . '?reference=' . rawurlencode($tid);

        $response = Http::timeout(15)
            ->withHeaders([
                'X-API-KEY' => $key,
                'Accept' => 'application/json',
            ])
            ->get($url);

        $body = $response->json();
        $rawBody = $response->body();

        if (! $response->successful()) {
            $logBody = $body ?? (strlen($rawBody) > 200 ? substr($rawBody, 0, 200) . '...' : $rawBody);
            Log::warning('Legacy data order status failed', ['reference' => $tid, 'status' => $response->status(), 'body' => $logBody]);
            return [
                'success' => false,
                'message' => $response->status() === 404
                    ? 'Order status endpoint not available or reference not found.'
                    : 'Failed to fetch order status (HTTP ' . $response->status() . ').',
            ];
        }

        if (! is_array($body) || ($body['status'] ?? '') !== 'success') {
            $msg = is_array($body) && isset($body['data']['message']) ? (string) $body['data']['message'] : 'Invalid response from API.';
            return ['success' => false, 'message' => $msg];
        }

        $data = $body['data'] ?? [];

        // Support multiple response shapes: single order with status, or order_items / orders array
        $orderItems = [];
        if (isset($data['order_items']) && is_array($data['order_items'])) {
            $orderItems = $data['order_items'];
        } elseif (isset($data['orders']) && is_array($data['orders'])) {
            $orderItems = $data['orders'];
        } elseif (isset($data['status']) || array_key_exists('status', $data)) {
            $status = (string) ($data['status'] ?? '');
            $beneficiary = (string) ($data['beneficiary'] ?? $data['beneficiary_number'] ?? $data['recipient'] ?? '');
            $orderItems = [['status' => $status, 'beneficiary_number' => $beneficiary]];
        }

        if (count($orderItems) === 0) {
            Log::info('Legacy data order status: no order items in response', ['reference' => $tid, 'data_keys' => array_keys($data)]);
            return ['success' => false, 'message' => 'API returned no order status.'];
        }

        // Normalize each item to have status and beneficiary_number
        $normalized = [];
        foreach ($orderItems as $item) {
            if (! is_array($item)) {
                continue;
            }
            $normalized[] = [
                'status' => (string) ($item['status'] ?? $item['order_status'] ?? ''),
                'beneficiary_number' => (string) ($item['beneficiary_number'] ?? $item['beneficiary'] ?? $item['recipient'] ?? $item['phone'] ?? ''),
            ];
        }
        if (count($normalized) === 0) {
            return ['success' => false, 'message' => 'API returned no valid order items.'];
        }

        return [
            'success' => true,
            'order_items' => $normalized,
        ];
    }

    /**
     * Map API order status to our internal status (delivered / failed / processing).
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

    private static function parseVolumeFromPlanName(string $planName): float
    {
        $s = trim($planName);
        if (preg_match('/^([\d.]+)\s*GB$/i', $s, $m)) {
            return (float) $m[1];
        }
        if (preg_match('/^([\d.]+)\s*MB$/i', $s, $m)) {
            return round((float) $m[1] / 1024, 4);
        }
        // Only match number directly before GB/MB to avoid treating price (e.g. 54.50) as volume
        if (preg_match('/([\d.]+)\s*GB/i', $s, $m)) {
            return (float) $m[1];
        }
        if (preg_match('/([\d.]+)\s*MB/i', $s, $m)) {
            return round((float) $m[1] / 1024, 4);
        }
        return 0;
    }

    private static function networkNameToId(string $name): int
    {
        $n = strtoupper(trim($name));
        if (str_contains($n, 'MTN')) {
            return 1;
        }
        if (str_contains($n, 'AIRTEL') || str_contains($n, 'AT') || str_contains($n, 'TIGO')) {
            return 2;
        }
        if (str_contains($n, 'TELECEL')) {
            return 3;
        }
        return 0;
    }

    private static function networkIdToName(int $networkId): string
    {
        $map = [1 => 'MTN', 2 => 'AirtelTigo', 3 => 'Telecel', 4 => 'AirtelTigo', 5 => 'MTN'];
        return $map[$networkId] ?? '';
    }

    private static function sharedBundleToDataPlan(int $sharedBundle): string
    {
        if ($sharedBundle <= 0) {
            return '';
        }
        $gb = round($sharedBundle / 1000, 2);
        if ($gb >= 1) {
            return $gb . 'GB';
        }
        $mb = (int) round($sharedBundle);
        return $mb . 'MB';
    }

    /**
     * Normalize plan name for API (e.g. "1 GB" -> "1GB") so it matches the provider's bundle list.
     */
    private static function normalizeDataPlanForApi(string $planName): string
    {
        $s = trim($planName);
        if ($s === '') {
            return '';
        }
        $s = preg_replace('/\s+([Gg][Bb]|[Mm][Bb])/i', '$1', $s);
        return $s;
    }
}
