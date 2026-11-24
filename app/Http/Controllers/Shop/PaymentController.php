<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Shop\Models\Order;
use App\Shop\Models\Payment;
use App\Shop\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentController
{
    public function pay(Order $order, PaystackService $paystack): RedirectResponse
    {
        if ($order->payment_status === 'paid' || $order->status === Order::STATUS_REFUNDED) {
            return redirect($this->orderUrl($order));
        }

        if (! $paystack->isConfigured()) {
            return redirect($this->orderUrl($order))->with(
                'warning',
                'Online payment is not configured yet. Your order is saved and pending; contact support to complete payment.'
            );
        }

        try {
            $payment = $paystack->initialize($order);
            $url = $payment->provider_response['authorization_url'] ?? null;

            if (! is_string($url) || $url === '') {
                throw ValidationException::withMessages(['payment' => 'Paystack did not return a payment URL.']);
            }

            return redirect()->away($url);
        } catch (Throwable $exception) {
            Log::error('Paystack initialization failed.', [
                'order' => $order->order_number,
                'error' => $exception->getMessage(),
            ]);

            return redirect($this->orderUrl($order))->with(
                'warning',
                'We could not start the payment. Your order is saved and pending; please try again or contact support.'
            );
        }
    }

    public function callback(Request $request, PaystackService $paystack): RedirectResponse
    {
        $reference = $request->string('reference')->toString();
        $payment = $reference !== '' ? Payment::query()->where('reference', $reference)->first() : null;
        $order = $payment?->order;

        if ($reference === '' || $order === null) {
            return redirect()->route('shop.index')->with('warning', 'Payment reference was not recognised.');
        }

        try {
            $paystack->verify($reference);

            return redirect($this->orderUrl($order))->with('status', 'Payment received. Thank you.');
        } catch (Throwable $exception) {
            Log::warning('Paystack verification failed.', [
                'reference' => $reference,
                'error' => $exception->getMessage(),
            ]);

            return redirect($this->orderUrl($order))->with(
                'warning',
                'We could not confirm the payment yet. If you were charged, contact support with your order number.'
            );
        }
    }

    private function orderUrl(Order $order): string
    {
        return URL::temporarySignedRoute('shop.orders.show', now()->addDay(), [
            'order' => $order->order_number,
        ]);
    }
}
