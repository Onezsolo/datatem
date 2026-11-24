<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Requests\TrackOrderRequest;
use App\Shop\Models\Order;
use App\Shop\Services\CheckoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;

class OrderController
{
    public function show(Order $order): View
    {
        $order->load(['items', 'payments']);

        return view('shop.order', compact('order'));
    }

    public function trackForm(): View
    {
        return view('shop.track');
    }

    public function track(TrackOrderRequest $request, CheckoutService $checkout): RedirectResponse
    {
        $phone = $checkout->normalisePhone($request->string('phone')->toString());
        $order = Order::query()
            ->where('order_number', $request->string('order_number')->trim()->toString())
            ->where('customer_phone', $phone)
            ->first();

        if ($order === null) {
            return back()
                ->withInput()
                ->withErrors(['order_number' => 'No order matched that order number and phone number.']);
        }

        $url = URL::temporarySignedRoute('shop.orders.show', now()->addHours(24), [
            'order' => $order->order_number,
        ]);

        return redirect($url);
    }
}
