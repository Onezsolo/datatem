<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Shop\Models\CartItem;
use App\Shop\Models\Product;
use App\Shop\Models\ProductVariant;
use App\Shop\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController
{
    public function show(Request $request, CartService $carts): View
    {
        $cart = $carts->current($request);

        if ($cart !== null) {
            $messages = $carts->revalidate($cart);

            if ($messages !== []) {
                session()->now('status', implode(' ', $messages));
            }

            $cart->load(['items.product.primaryImage', 'items.variant']);
        }

        return view('shop.cart', ['cart' => $cart]);
    }

    public function add(StoreCartItemRequest $request, CartService $carts): RedirectResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));
        $variant = $request->filled('variant_id')
            ? ProductVariant::query()->findOrFail($request->integer('variant_id'))
            : null;

        $carts->add($request, $product, $variant, $request->integer('quantity', 1));

        return back()->with('status', $product->name.' was added to your cart.');
    }

    public function update(UpdateCartItemRequest $request, CartItem $item, CartService $carts): RedirectResponse
    {
        $cart = $carts->current($request);

        abort_if($cart === null || $item->cart_id !== $cart->id, 404);

        $carts->update($request, $item, $request->integer('quantity'));

        return redirect()->route('shop.cart.show')->with('status', 'Cart updated.');
    }

    public function remove(Request $request, CartItem $item, CartService $carts): RedirectResponse
    {
        $cart = $carts->current($request);

        abort_if($cart === null || $item->cart_id !== $cart->id, 404);

        $carts->remove($request, $item);

        return redirect()->route('shop.cart.show')->with('status', 'Item removed from your cart.');
    }

    public function clear(Request $request, CartService $carts): RedirectResponse
    {
        $cart = $carts->current($request);

        if ($cart !== null) {
            $carts->clear($cart);
        }

        return redirect()->route('shop.cart.show')->with('status', 'Your cart is empty.');
    }
}
