<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Requests\CheckoutRequest;
use App\Shop\Services\CartService;
use App\Shop\Services\CheckoutService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class CheckoutController
{
    public function show(Request $request, CartService $carts): View|RedirectResponse
    {
        $cart = $carts->current($request);

        if ($cart === null || $cart->items()->doesntExist()) {
            return redirect()->route('shop.cart.show')->with('status', 'Add an item to your cart before checkout.');
        }

        $messages = $carts->revalidate($cart);
        $cart->load(['items.product.primaryImage', 'items.variant']);

        if ($cart->items->isEmpty()) {
            return redirect()->route('shop.cart.show')->with('status', 'Your cart is empty.');
        }

        return view('shop.checkout', [
            'cart' => $cart,
            'zones' => config('delivery.zones'),
            'messages' => $messages,
        ]);
    }

    public function store(CheckoutRequest $request, CartService $carts, CheckoutService $checkout): RedirectResponse
    {
        $cart = $carts->current($request);

        if ($cart === null) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        $order = $checkout->placeOrder($cart, $request->validated());

        $request->session()->forget(CartService::SESSION_KEY);

        $url = URL::temporarySignedRoute('shop.orders.show', now()->addDay(), [
            'order' => $order->order_number,
        ]);

        return redirect($url)->with('status', 'Order '.$order->order_number.' was created.');
    }
}
