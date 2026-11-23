<?php

namespace App\Services;

use App\Models\DataPackage;
use App\Models\Purchase;
use App\Models\StorefrontPayment;
use App\Services\Refer2BundleService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Fulfill a storefront payment: verify Paystack payment, call API to place order, create Purchase, mark payment completed.
 * Does NOT use platform balance (customer has already paid via Paystack; that covers the order).
 * Does NOT credit reseller here (reseller is credited when order status becomes 'delivered').
 */
final class StorefrontFulfillmentService
{
    /**
     * Fulfill a pending storefront payment. Caller must have verified the payment with Paystack and passed the paid amount.
     *
     * @param  float|null  $amountPaidGhs  Amount paid (GHS) from Paystack verify; if null, will verify via PaystackService
     * @return array{ok: bool, ref?: string, error?: string}
     */
    public static function fulfill(StorefrontPayment $payment, ?float $amountPaidGhs = null): array
    {
        Log::channel('storefront')->info('StorefrontFulfillment: fulfill start', [
            'payment_id' => $payment->id,
            'reseller_id' => $payment->reseller_id,
            'beneficiary' => $payment->beneficiary,
            'amount_charged' => $payment->amount_charged,
            'reseller_profit' => $payment->reseller_profit,
        ]);

        $payment->loadMissing(['reseller', 'dataPackage']);
        $package = $payment->dataPackage;
        $reseller = $payment->reseller;
        if (! $package || ! $reseller) {
            Log::channel('storefront')->error('StorefrontFulfillment: invalid_data', ['payment_id' => $payment->id, 'has_package' => (bool) $package, 'has_reseller' => (bool) $reseller]);
            return ['ok' => false, 'error' => 'invalid_data'];
        }

        if ($amountPaidGhs === null) {
            Log::channel('storefront')->info('StorefrontFulfillment: verifying via Paystack');
            $paystack = new PaystackService();
            $verify = $paystack->verifyTransaction($payment->id);
            if (! ($verify['success'] ?? false) || ! ($verify['verified'] ?? false)) {
                Log::channel('storefront')->warning('StorefrontFulfillment: Paystack verify failed', ['payment_id' => $payment->id, 'message' => $verify['message'] ?? 'unknown']);
                return ['ok' => false, 'error' => $verify['message'] ?? 'Payment verification failed'];
            }
            $amountPaidGhs = (float) ($verify['amount'] ?? 0);
            Log::channel('storefront')->info('StorefrontFulfillment: Paystack verified', ['amount_ghs' => $amountPaidGhs]);
        }

        $expectedAmount = (float) ($payment->amount_charged ?? $payment->amount);
        if (abs($amountPaidGhs - $expectedAmount) > 0.01) {
            Log::channel('storefront')->warning('StorefrontFulfillment: amount mismatch', ['expected' => $expectedAmount, 'paid' => $amountPaidGhs]);
            return ['ok' => false, 'error' => 'Payment amount mismatch'];
        }

        // Storefront: customer has already paid via Paystack; we do not deduct from platform or reseller balance.
        // We only place the order with the API and later credit the reseller their profit when delivered.

        $planLabel = $package->volume_gb ?: $package->volume . ' GB';
        $ref = 'STF-' . strtoupper(Str::random(8));
        $networkId = (int) ($package->network_id ?? 0);
        $volume = (float) $package->volume;
        $sharedBundle = $volume > 0 ? (int) round($volume * 1000) : 0;
        $dataPlanOverride = $package->volume_gb ? trim((string) $package->volume_gb) : null;
        $providerBundleId = $package->provider_bundle_id ? trim((string) $package->provider_bundle_id) : null;
        $networkNameOverride = $package->network ? trim((string) $package->network) : null;
        // If this payment was for the wrong MTN 1 GB (54.50) or Express(MTN) 1 GB (64.50), use the correct MTN 1 GB so the API charges ~4.50 and we send network "MTN".
        if (DataPackage::shouldUseCorrectMtn1GbForApi($package)) {
            $correctMtn1Gb = DataPackage::getCorrectMtn1GbPackage();
            if ($correctMtn1Gb) {
                $dataPlanOverride = $correctMtn1Gb->volume_gb ? trim((string) $correctMtn1Gb->volume_gb) : '1 GB';
                $providerBundleId = $correctMtn1Gb->provider_bundle_id ? trim((string) $correctMtn1Gb->provider_bundle_id) : null;
                $networkNameOverride = $correctMtn1Gb->network ? trim((string) $correctMtn1Gb->network) : 'MTN';
                $networkId = (int) ($correctMtn1Gb->network_id ?? $networkId);
                $sharedBundle = $correctMtn1Gb->volume > 0 ? (int) round((float) $correctMtn1Gb->volume * 1000) : $sharedBundle;
            }
        }
        Log::channel('storefront')->info('StorefrontFulfillment: calling Refer2Bundle API', ['beneficiary' => $payment->beneficiary, 'network_id' => $networkId, 'shared_bundle' => $sharedBundle]);
        $apiResult = Refer2BundleService::buyPackage($payment->beneficiary, $networkId, $sharedBundle, $dataPlanOverride, $providerBundleId, $networkNameOverride);
        Log::channel('storefront')->info('StorefrontFulfillment: Refer2Bundle API result', ['success' => $apiResult['success'] ?? false, 'message' => $apiResult['message'] ?? null, 'transaction_code' => $apiResult['transaction_code'] ?? null]);

        if ($apiResult['success']) {
            $transactionCode = $apiResult['transaction_code'] ?? null;
            Purchase::create([
                'user_id' => null,
                'reseller_id' => $reseller->id,
                'reseller_profit' => $payment->reseller_profit,
                'data_package_id' => $package->id,
                'network' => $package->network,
                'data_plan' => $planLabel,
                'beneficiary' => $payment->beneficiary,
                'amount' => $payment->amount,
                'reference' => $ref,
                'transaction_id' => $transactionCode,
                'failure_reason' => null,
                'status' => 'processing',
            ]);
            Log::channel('storefront')->info('StorefrontFulfillment: purchase created (sent to API)', [
                'purchase_ref' => $ref,
                'reseller_id' => $reseller->id,
                'reseller_profit' => $payment->reseller_profit,
                'transaction_id' => $transactionCode,
                'status' => 'processing',
            ]);
        } else {
            Purchase::create([
                'user_id' => null,
                'reseller_id' => $reseller->id,
                'reseller_profit' => $payment->reseller_profit,
                'data_package_id' => $package->id,
                'network' => $package->network,
                'data_plan' => $planLabel,
                'beneficiary' => $payment->beneficiary,
                'amount' => $payment->amount,
                'reference' => $ref,
                'transaction_id' => null,
                'failure_reason' => $apiResult['message'] ?? 'API failed',
                'status' => 'pending',
            ]);
            Log::channel('storefront')->warning('StorefrontFulfillment: purchase created (API failed, pending)', [
                'purchase_ref' => $ref,
                'reseller_id' => $reseller->id,
                'reseller_profit' => $payment->reseller_profit,
                'failure_reason' => $apiResult['message'] ?? 'API failed',
                'status' => 'pending',
            ]);
        }

        $payment->update(['status' => 'completed']);
        Log::channel('storefront')->info('StorefrontFulfillment: payment marked completed', ['payment_id' => $payment->id, 'purchase_ref' => $ref]);
        return ['ok' => true, 'ref' => $ref];
    }
}
