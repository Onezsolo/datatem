<?php

namespace App\Http\Controllers;

use App\Models\DataPackage;
use App\Models\Purchase;
use App\Models\ResellerPackagePrice;
use App\Models\StorefrontPayment;
use App\Models\User;
use App\Services\LegacyDataService;
use App\Services\PaystackService;
use App\Services\SettingsService;
use App\Services\StorefrontFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StorefrontController extends Controller
{
    /**
     * Get reseller by store slug (public). Uses store_slug first (e.g. iksoft), then fallback to reseller_slug (e.g. r5).
     */
    public static function resellerBySlug(string $slug): ?User
    {
        $reseller = User::where('store_slug', $slug)->first();
        if ($reseller) {
            return $reseller;
        }
        return User::where('reseller_slug', $slug)->first();
    }

    /**
     * Public API: list packages for a reseller's storefront with their selling prices.
     * Store is only accessible when store_slug, store_name and store_whatsapp are set.
     */
    public function packages(string $slug): JsonResponse
    {
        $reseller = self::resellerBySlug($slug);
        if (! $reseller || ! $reseller->isReseller()) {
            return response()->json(['error' => 'Store not found.'], 404);
        }
        if (empty(trim((string) $reseller->store_slug)) || empty(trim((string) $reseller->store_name)) || empty(trim((string) $reseller->store_whatsapp))) {
            return response()->json(['error' => 'This store is not set up yet.'], 404);
        }

        $packages = DataPackage::available()->excludeExpressNetworks()->excludeProviderBundleIds()->orderBy('network')->orderBy('volume')->get();
        $customPrices = ResellerPackagePrice::where('user_id', $reseller->id)->get()->keyBy('data_package_id');

        $list = $packages->map(function (DataPackage $p) use ($customPrices) {
            $basePrice = $p->selling_price !== null ? (float) $p->selling_price : (float) $p->console_price;
            $custom = $customPrices->get($p->id);
            $price = $custom ? (float) $custom->selling_price : $basePrice;
            return [
                'id' => $p->id,
                'network' => $p->network ?? '',
                'volume_gb' => $p->volume_gb ?: $p->volume . ' GB',
                'price' => round($price, 2),
            ];
        });

        $storeName = trim((string) $reseller->store_name);
        $whatsapp = preg_replace('/\D/', '', (string) $reseller->store_whatsapp);
        $whatsappUrl = $whatsapp !== '' ? 'https://wa.me/' . (str_starts_with($whatsapp, '233') ? $whatsapp : '233' . ltrim($whatsapp, '0')) : null;

        return response()->json([
            'reseller_name' => $storeName,
            'store_whatsapp' => $reseller->store_whatsapp,
            'store_whatsapp_url' => $whatsappUrl,
            'packages' => $list,
        ]);
    }

    /**
     * Normalize Ghana mobile number for comparison: digits only, 0XXXXXXXXX.
     */
    private static function normalizePhone(string $number): string
    {
        $digits = preg_replace('/\D/', '', $number);
        if (str_starts_with($digits, '233') && strlen($digits) >= 12) {
            return '0' . substr($digits, 3, 9);
        }
        if (strlen($digits) === 9 && in_array($digits[0], ['2', '3', '5'], true)) {
            return '0' . $digits;
        }
        return strlen($digits) >= 10 ? ('0' . substr($digits, -9)) : $digits;
    }

    /**
     * Track orders by customer phone number (beneficiary). Public, no auth.
     * GET /api/store/{slug}/track?number=0551234567
     */
    public function trackOrders(Request $request, string $slug): JsonResponse
    {
        $reseller = self::resellerBySlug($slug);
        if (! $reseller || ! $reseller->isReseller()) {
            return response()->json(['error' => 'Store not found.'], 404);
        }

        $number = trim((string) $request->query('number', $request->input('number', '')));
        if ($number === '') {
            return response()->json(['error' => 'Please enter your mobile number.'], 422);
        }

        $normalized = self::normalizePhone($number);
        if (strlen($normalized) < 10) {
            return response()->json(['error' => 'Invalid mobile number.'], 422);
        }

        $purchases = Purchase::where('reseller_id', $reseller->id)
            ->orderByDesc('created_at')
            ->get()
            ->filter(function (Purchase $p) use ($normalized) {
                return self::normalizePhone((string) $p->beneficiary) === $normalized;
            })
            ->values()
            ->map(function (Purchase $p) {
                return [
                    'reference' => $p->reference,
                    'network' => $p->network,
                    'data_plan' => $p->data_plan,
                    'beneficiary' => $p->beneficiary,
                    'amount' => (float) $p->amount,
                    'status' => $p->status,
                    'created_at' => $p->created_at->toIso8601String(),
                    'created_at_formatted' => $p->created_at->format('M d, Y H:i'),
                ];
            });

        return response()->json([
            'orders' => $purchases,
            'number' => $number,
        ]);
    }

    /**
     * Initialize Paystack for storefront (guest) purchase. POST package_id, beneficiary, customer_email.
     */
    public function checkout(Request $request, string $slug): JsonResponse
    {
        $reseller = self::resellerBySlug($slug);
        if (! $reseller || ! $reseller->isReseller()) {
            return response()->json(['error' => 'Store not found.'], 404);
        }
        if (empty(trim((string) $reseller->store_slug)) || empty(trim((string) $reseller->store_name)) || empty(trim((string) $reseller->store_whatsapp))) {
            return response()->json(['error' => 'This store is not set up yet.'], 404);
        }

        $data = $request->validate([
            'package_id' => 'required|integer|exists:data_packages,id',
            'beneficiary' => 'required|string|max:20',
            'customer_email' => 'nullable|email',
        ]);

        $beneficiary = preg_replace('/\s+/', '', $data['beneficiary']);
        if (! preg_match('/^0[235]\d{8}$/', $beneficiary)) {
            throw ValidationException::withMessages(['beneficiary' => ['Please enter a valid Ghana mobile number.']]);
        }

        $package = DataPackage::available()
            ->excludeExpressNetworks()
            ->excludeProviderBundleIds()
            ->find($data['package_id']);
        if (! $package) {
            return response()->json(['error' => 'Package not found.'], 404);
        }

        $basePrice = $package->selling_price !== null ? (float) $package->selling_price : (float) $package->console_price;
        $custom = ResellerPackagePrice::where('user_id', $reseller->id)->where('data_package_id', $package->id)->first();
        $amount = $custom ? (float) $custom->selling_price : $basePrice;
        $amount = round($amount, 2);
        $resellerProfit = round($amount - $basePrice, 2);

        if ($amount < 0.01) {
            return response()->json(['error' => 'Invalid price.'], 400);
        }

        $feePercent = 4;
        $amountCharged = round($amount + round($amount * ($feePercent / 100), 2), 2);

        $reference = 'STF-' . strtoupper(Str::random(12));

        Log::channel('storefront')->info('Storefront checkout started', [
            'reference' => $reference,
            'slug' => $slug,
            'reseller_id' => $reseller->id,
            'package_id' => $package->id,
            'beneficiary' => $beneficiary,
            'amount' => $amount,
            'reseller_profit' => $resellerProfit,
        ]);

        $paystackEmail = $reseller->email ?: config('mail.from.address', 'support@example.com');
        if (empty(trim((string) $paystackEmail))) {
            Log::channel('storefront')->warning('Storefront checkout: no reseller email', ['reference' => $reference, 'reseller_id' => $reseller->id]);
            return response()->json(['error' => 'This store cannot accept payments yet. Please contact support.'], 400);
        }

        StorefrontPayment::create([
            'id' => $reference,
            'reseller_id' => $reseller->id,
            'data_package_id' => $package->id,
            'beneficiary' => $beneficiary,
            'amount' => $amount,
            'amount_charged' => $amountCharged,
            'reseller_profit' => $resellerProfit,
            'base_amount' => $basePrice,
            'status' => 'pending',
            'customer_email' => $data['customer_email'] ?? null,
        ]);

        $callbackUrl = url('/store/' . $slug . '/callback?reference=' . $reference);
        $paystack = new PaystackService();
        $result = $paystack->initializeTransaction(
            $paystackEmail,
            $amountCharged,
            $reference,
            $callbackUrl,
            ['storefront' => '1', 'reseller_id' => (string) $reseller->id]
        );

        if (! ($result['success'] ?? false)) {
            Log::channel('storefront')->warning('Storefront Paystack init failed', [
                'reference' => $reference,
                'reseller_id' => $reseller->id,
                'message' => $result['message'] ?? 'unknown',
            ]);
            return response()->json([
                'error' => $result['message'] ?? 'Could not start payment.',
            ], 400);
        }

        Log::channel('storefront')->info('Storefront checkout: Paystack init success', ['reference' => $reference]);
        return response()->json([
            'authorization_url' => $result['authorization_url'],
            'reference' => $result['reference'] ?? $reference,
        ]);
    }

    /**
     * Paystack redirects here after storefront payment. Verify and fulfill order.
     * Reseller profit is credited when order status becomes 'delivered', not here.
     */
    public function callback(Request $request, string $slug)
    {
        $reference = $request->query('reference');
        Log::channel('storefront')->info('Storefront callback hit', ['slug' => $slug, 'reference' => $reference ?? 'null']);
        if (! $reference) {
            return redirect('/store/' . $slug . '?error=missing_reference');
        }

        $payment = StorefrontPayment::with(['reseller', 'dataPackage'])->where('id', $reference)->first();
        if (! $payment) {
            Log::channel('storefront')->warning('Storefront callback: payment not found', ['reference' => $reference]);
            return redirect('/store/' . $slug . '?error=invalid_reference');
        }

        if ($payment->status !== 'pending') {
            Log::channel('storefront')->info('Storefront callback: already processed', ['reference' => $reference, 'status' => $payment->status]);
            return redirect('/store/' . $slug . '?success=1&already_processed=1');
        }

        $result = StorefrontFulfillmentService::fulfill($payment, null);
        if (! ($result['ok'] ?? false)) {
            Log::channel('storefront')->error('Storefront callback: fulfill failed', ['reference' => $reference, 'error' => $result['error'] ?? 'unknown']);
            return redirect('/store/' . $slug . '?error=' . urlencode($result['error'] ?? 'Payment could not be fulfilled.'));
        }
        Log::channel('storefront')->info('Storefront callback: fulfill success', ['reference' => $reference, 'purchase_ref' => $result['ref'] ?? null]);
        return redirect('/store/' . $slug . '?success=1&ref=' . urlencode($result['ref'] ?? ''));
    }

    /**
     * Paystack webhook: fulfill storefront payments when charge.success so orders are created even if user never hits callback.
     * Reseller profit is credited when order status becomes 'delivered', not here.
     */
    public function paystackWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-Paystack-Signature', '');
        $paystack = new PaystackService();
        if (! $paystack->verifyWebhookSignature($payload, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $data = json_decode($payload, true);
        $event = $data['event'] ?? '';
        Log::channel('storefront')->info('Storefront webhook received', ['event' => $event]);
        if ($event !== 'charge.success') {
            return response()->json(['message' => 'Ignored'], 200);
        }

        $reference = (string) ($data['data']['reference'] ?? '');
        if (! str_starts_with($reference, 'STF-')) {
            return response()->json(['message' => 'Not a storefront payment'], 200);
        }

        $payment = StorefrontPayment::with(['reseller', 'dataPackage'])->where('id', $reference)->first();
        if (! $payment || $payment->status !== 'pending') {
            Log::channel('storefront')->warning('Storefront webhook: payment not found or not pending', [
                'reference' => $reference,
                'found' => (bool) $payment,
                'status' => $payment?->status,
            ]);
            return response()->json(['message' => 'Payment not found or already processed'], 200);
        }

        // Amount from webhook is in pesewas (GHS * 100)
        $amountPesewas = (int) ($data['data']['amount'] ?? 0);
        $amountPaidGhs = $amountPesewas / 100;
        Log::channel('storefront')->info('Storefront webhook: fulfilling', ['reference' => $reference, 'amount_ghs' => $amountPaidGhs]);
        $result = StorefrontFulfillmentService::fulfill($payment, $amountPaidGhs);
        Log::channel('storefront')->info('Storefront webhook: fulfill result', ['reference' => $reference, 'ok' => $result['ok'] ?? false, 'error' => $result['error'] ?? null, 'ref' => $result['ref'] ?? null]);
        // Always return 200 so Paystack does not retry
        return response()->json(['message' => $result['ok'] ? 'Fulfilled' : 'Fulfillment failed'], 200);
    }
}
