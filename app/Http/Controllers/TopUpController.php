<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Topup;
use App\Models\User;
use App\Services\NotifyService;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * User wallet top-up: Paystack (automated) and manual flow.
 */
class TopUpController extends Controller
{
    /**
     * Mark a pending payment as completed, credit user wallet, and create topup record.
     */
    private function completeVerifiedPayment(Payment $payment): array
    {
        $user = $payment->user;
        if (! $user) {
            return ['ok' => false, 'message' => 'User not found.'];
        }

        $amount = (float) $payment->amount;

        $payment->status = 'completed';
        $payment->save();

        $user->balance = (float) $user->balance + $amount;
        $user->save();

        $topup = Topup::create([
            'id' => 'TUP-' . strtoupper(Str::random(12)),
            'user_id' => $user->id,
            'amount' => $amount,
            'method' => 'paystack',
            'status' => 'approved',
        ]);

        NotifyService::notifyUserTopupCompleted($user, $topup);
        NotifyService::admins(
            'Wallet topup',
            $user->name . ' topped up GH₵ ' . number_format($amount, 2) . ' via Paystack. Ref: ' . $topup->id . '.',
            'info',
            url('/admin/topups')
        );

        return ['ok' => true, 'amount' => $amount];
    }

    /**
     * Initialize a Paystack transaction for wallet top-up.
     * Returns authorization_url to redirect the user to Paystack.
     */
    public function initialize(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:5|max:10000',
        ], [
            'amount.min' => 'Minimum deposit is GH₵ 5.00.',
        ]);

        $amountToCredit = round((float) $data['amount'], 2);
        $feePercent = 4;
        $fee = round($amountToCredit * ($feePercent / 100), 2);
        $amountToCharge = round($amountToCredit + $fee, 2);

        $user = $request->user();

        if (!$user->email) {
            throw ValidationException::withMessages([
                'email' => ['Your account has no email. Please add an email in profile to use Paystack.'],
            ]);
        }

        $reference = 'TUP-' . strtoupper(Str::random(14));

        $callbackUrl = url('/topup/callback?reference=' . $reference);

        $paystack = new PaystackService();
        $result = $paystack->initializeTransaction(
            $user->email,
            $amountToCharge,
            $reference,
            $callbackUrl,
            ['user_id' => (string) $user->id]
        );

        if (!($result['success'] ?? false)) {
            throw ValidationException::withMessages([
                'amount' => [$result['message'] ?? 'Could not start payment.'],
            ]);
        }

        // Store pending payment: amount = what we credit (user gets this); amount_charged = what Paystack will charge (for verification)
        Payment::create([
            'id' => $reference,
            'user_id' => $user->id,
            'amount' => $amountToCredit,
            'amount_charged' => $amountToCharge,
            'reference' => $reference,
            'status' => 'pending',
        ]);

        return response()->json([
            'authorization_url' => $result['authorization_url'],
            'reference' => $result['reference'],
            'access_code' => $result['access_code'] ?? null,
        ]);
    }

    /**
     * Paystack redirects here after payment. Verify and credit user.
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference');
        if (!$reference) {
            return redirect('/topup?error=missing_reference');
        }

        $payment = Payment::with('user')->where('id', $reference)->first();
        if (!$payment) {
            return redirect('/topup?error=invalid_reference');
        }

        if ($payment->status !== 'pending') {
            return redirect('/topup?success=1&already_credited=1');
        }

        $paystack = new PaystackService();
        $verify = $paystack->verifyTransaction($reference);

        if (!($verify['success'] ?? false) || !($verify['verified'] ?? false)) {
            return redirect('/topup?error=' . urlencode($verify['message'] ?? 'Verification failed'));
        }

        // Security: only credit the amount we actually received from Paystack (amount is in GHS from verify)
        $amountPaid = round((float) ($verify['amount'] ?? 0), 2);
        $expectedCharged = $payment->amount_charged !== null && $payment->amount_charged !== ''
            ? round((float) $payment->amount_charged, 2)
            : round((float) $payment->amount + round((float) $payment->amount * 0.03, 2), 2);
        if (abs($amountPaid - $expectedCharged) > 0.02) {
            Log::warning('TopUp Paystack amount mismatch', [
                'reference' => $reference,
                'amount_paid' => $amountPaid,
                'expected_charged' => $expectedCharged,
                'payment_amount' => (float) $payment->amount,
                'payment_amount_charged' => $payment->amount_charged,
            ]);
            return redirect('/topup?error=' . urlencode('Payment amount mismatch. Please contact support.'));
        }

        $result = $this->completeVerifiedPayment($payment);
        if (! ($result['ok'] ?? false)) {
            return redirect('/topup?error=' . urlencode($result['message'] ?? 'Could not process payment.'));
        }

        return redirect('/topup?success=1&amount=' . ($result['amount'] ?? 0));
    }

    /**
     * Allow users to verify their own pending Paystack topups from the top-up page.
     */
    public function verifyOwnPaystackPayment(Request $request, string $reference): JsonResponse
    {
        $payment = Payment::with('user')
            ->where('id', $reference)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $payment) {
            return response()->json(['success' => false, 'message' => 'Payment not found for your account.'], 404);
        }

        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => true,
                'verified' => true,
                'status' => $payment->status,
                'message' => 'Payment already processed.',
                'amount' => (float) $payment->amount,
            ]);
        }

        $paystack = new PaystackService();
        $verify = $paystack->verifyTransaction($reference);

        if (! ($verify['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'verified' => false,
                'message' => $verify['message'] ?? 'Verification failed.',
            ], 400);
        }
        if (! ($verify['verified'] ?? false)) {
            return response()->json([
                'success' => false,
                'verified' => false,
                'message' => 'Payment is not completed on Paystack yet (status: ' . ($verify['status'] ?? 'unknown') . ').',
            ], 400);
        }

        $amountPaid = round((float) ($verify['amount'] ?? 0), 2);
        $expectedCharged = $payment->amount_charged !== null && $payment->amount_charged !== ''
            ? round((float) $payment->amount_charged, 2)
            : round((float) $payment->amount + round((float) $payment->amount * 0.03, 2), 2);
        if (abs($amountPaid - $expectedCharged) > 0.02) {
            Log::warning('User verify Paystack amount mismatch', [
                'reference' => $reference,
                'user_id' => $request->user()->id,
                'amount_paid' => $amountPaid,
                'expected_charged' => $expectedCharged,
                'payment_amount' => (float) $payment->amount,
                'payment_amount_charged' => $payment->amount_charged,
            ]);
            return response()->json([
                'success' => false,
                'verified' => false,
                'message' => 'Payment amount mismatch. Please contact support.',
            ], 400);
        }

        $result = $this->completeVerifiedPayment($payment);
        if (! ($result['ok'] ?? false)) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'Could not process payment.'], 400);
        }

        return response()->json([
            'success' => true,
            'verified' => true,
            'status' => 'completed',
            'message' => 'Payment verified and wallet credited successfully.',
            'amount' => $result['amount'],
        ]);
    }
}
