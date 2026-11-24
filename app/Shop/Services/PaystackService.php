<?php

declare(strict_types=1);

namespace App\Shop\Services;

use App\Shop\Models\Order;
use App\Shop\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaystackService
{
    public function isConfigured(): bool
    {
        return filled(config('services.paystack.secret_key'));
    }

    public function initialize(Order $order): Payment
    {
        if (! $this->isConfigured()) {
            throw ValidationException::withMessages(['payment' => 'Paystack is not configured.']);
        }

        $reference = 'DS-'.$order->order_number.'-'.Str::upper(Str::random(6));
        $amount = $this->amountInPesewas($order);

        $payment = Payment::create([
            'order_id' => $order->id,
            'reference' => $reference,
            'provider' => 'paystack',
            'amount' => $order->total,
            'currency' => $order->currency,
            'status' => Payment::STATUS_PENDING,
            'payment_method' => $order->payment_method ?? 'paystack',
        ]);

        try {
            $response = Http::acceptJson()
                ->withToken((string) config('services.paystack.secret_key'))
                ->post($this->endpoint('/transaction/initialize'), [
                    'email' => $order->customer_email ?: config('store.support_email'),
                    'amount' => $amount,
                    'currency' => $order->currency,
                    'reference' => $reference,
                    'callback_url' => route('shop.payments.callback'),
                    'metadata' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                    ],
                ])
                ->throw();

            $payload = (array) $response->json('data', []);

            $payment->update([
                'provider_reference' => $payload['reference'] ?? $reference,
                'provider_response' => $payload,
            ]);

            $order->update([
                'payment_reference' => $reference,
                'payment_meta' => [
                    'authorization_url' => $payload['authorization_url'] ?? null,
                    'access_code' => $payload['access_code'] ?? null,
                ],
            ]);

            return $payment->refresh();
        } catch (Throwable $exception) {
            $payment->markAsFailed(['error' => $exception->getMessage()]);

            throw $exception;
        }
    }

    public function verify(string $reference): Payment
    {
        if (! $this->isConfigured()) {
            throw ValidationException::withMessages(['payment' => 'Paystack is not configured.']);
        }

        $payment = $this->paymentForReference($reference);

        if ($payment === null) {
            throw ValidationException::withMessages(['payment' => 'Payment reference was not found.']);
        }

        $response = Http::acceptJson()
            ->withToken((string) config('services.paystack.secret_key'))
            ->get($this->endpoint('/transaction/verify/'.rawurlencode($reference)))
            ->throw();

        $data = (array) $response->json('data', []);

        if (($data['status'] ?? null) === 'success') {
            $this->markPaid($payment, $data);
        } else {
            $this->markFailed($payment, $data);

            throw ValidationException::withMessages(['payment' => 'Payment was not successful.']);
        }

        return $payment->refresh();
    }

    public function processWebhook(array $payload): void
    {
        $event = (string) ($payload['event'] ?? '');
        $data = (array) ($payload['data'] ?? []);
        $reference = (string) ($data['reference'] ?? '');

        if ($reference === '') {
            return;
        }

        $payment = $this->paymentForReference($reference);

        if ($payment === null) {
            Log::warning('Paystack webhook for unknown payment reference.', ['reference' => $reference]);

            return;
        }

        if ($event === 'charge.success') {
            $this->markPaid($payment, $data);
        }

        if ($event === 'charge.failed') {
            $this->markFailed($payment, $data);
        }

        if ($event === 'refund.processed') {
            $this->markRefunded($payment, $data, null, 'paystack_webhook');
        }
    }

    public function signatureValid(Request $request): bool
    {
        $signature = $request->header('x-paystack-signature');
        $secret = (string) config('services.paystack.secret_key');

        if (! is_string($signature) || $signature === '' || $secret === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $request->getContent(), $secret), $signature);
    }

    public function refund(Order $order): Payment
    {
        if (! $this->isConfigured()) {
            throw ValidationException::withMessages(['payment' => 'Paystack is not configured.']);
        }

        $payment = Payment::query()
            ->where('order_id', $order->id)
            ->where('status', Payment::STATUS_PAID)
            ->latest()
            ->first();

        if (! $payment instanceof Payment) {
            throw ValidationException::withMessages(['payment' => 'This order has no successful payment to refund.']);
        }

        $response = Http::acceptJson()
            ->withToken((string) config('services.paystack.secret_key'))
            ->post($this->endpoint('/refund'), [
                'transaction' => $payment->provider_reference ?? $payment->reference,
            ])
            ->throw();

        if (! $response->json('status')) {
            throw ValidationException::withMessages(['payment' => 'Paystack did not accept the refund request.']);
        }

        $this->markRefunded($payment, (array) $response->json('data', []), 'admin_refund');

        return $payment->refresh();
    }

    public function markPaid(Payment $payment, array $providerResponse): void
    {
        if (in_array($payment->status, [Payment::STATUS_PAID, Payment::STATUS_REFUNDED], true)) {
            return;
        }

        $order = DB::transaction(function () use ($payment, $providerResponse): Order {
            DB::table('shop_payments')->where('id', $payment->id)->lockForUpdate()->first();

            $locked = Payment::query()->whereKey($payment->id)->first();

            if (! $locked instanceof Payment) {
                throw ValidationException::withMessages(['payment' => 'The payment was not found.']);
            }

            if ($locked->status === Payment::STATUS_PAID) {
                return $locked->order;
            }

            $this->assertAmountAndCurrency($locked, $providerResponse);

            $locked->markAsPaid($providerResponse);
            $locked->order->markAsPaid();
            $locked->order->update(['payment_reference' => $locked->reference]);

            return $locked->order;
        });

        app(NotificationService::class)->orderPaid($order);
    }

    public function markFailed(Payment $payment, array $providerResponse): void
    {
        if ($payment->status === Payment::STATUS_PAID || $payment->status === Payment::STATUS_REFUNDED) {
            return;
        }

        $payment->markAsFailed($providerResponse);
        $payment->order->update(['payment_status' => 'failed']);
    }

    private function markRefunded(Payment $payment, array $providerResponse, string $reason): void
    {
        $order = DB::transaction(function () use ($payment, $providerResponse, $reason): Order {
            DB::table('shop_payments')->where('id', $payment->id)->lockForUpdate()->first();

            $locked = Payment::query()->whereKey($payment->id)->first();

            if (! $locked instanceof Payment) {
                throw ValidationException::withMessages(['payment' => 'The payment was not found.']);
            }

            if ($locked->status === Payment::STATUS_REFUNDED) {
                return $locked->order;
            }

            $locked->update([
                'status' => Payment::STATUS_REFUNDED,
                'refunded_at' => now(),
                'provider_response' => $providerResponse,
            ]);
            $locked->order->markAsRefunded();

            return $locked->order;
        });

        app(NotificationService::class)->orderStatus($order);
    }

    private function assertAmountAndCurrency(Payment $payment, array $providerResponse): void
    {
        $expected = $this->amountInPesewas($payment->order);
        $actual = (int) ($providerResponse['amount'] ?? 0);
        $currency = (string) ($providerResponse['currency'] ?? '');

        if ($actual !== $expected || strtoupper($currency) !== strtoupper($payment->currency)) {
            throw ValidationException::withMessages([
                'payment' => 'Payment amount or currency did not match the order.',
            ]);
        }
    }

    private function paymentForReference(string $reference): ?Payment
    {
        return Payment::query()
            ->where('reference', $reference)
            ->orWhere('provider_reference', $reference)
            ->first();
    }

    private function amountInPesewas(Order $order): int
    {
        return (int) round((float) $order->total * 100);
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.paystack.base_url', 'https://api.paystack.co'), '/').$path;
    }
}
