<?php

declare(strict_types=1);

namespace App\Shop\Services;

use App\Shop\Models\Cart;
use App\Shop\Models\CartItem;
use App\Shop\Models\Product;
use App\Shop\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const SESSION_KEY = 'cart_token';

    public function current(Request $request, bool $create = false): ?Cart
    {
        $token = $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;
        $token = is_string($token) && $token !== '' ? $token : $request->header('X-Cart-Token');
        $token = is_string($token) && $token !== '' ? $token : null;

        if ($token !== null) {
            $cart = Cart::query()
                ->where('token', $token)
                ->where(function (Builder $query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->first();

            if ($cart instanceof Cart) {
                $this->remember($request, $cart);

                return $cart;
            }
        }

        if (! $create) {
            return null;
        }

        $cart = Cart::create([
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $this->remember($request, $cart);

        return $cart;
    }

    public function add(Request $request, Product $product, ?ProductVariant $variant, int $quantity): Cart
    {
        if (! $product->is_active) {
            throw ValidationException::withMessages(['product' => 'This product is not available.']);
        }

        if ($variant !== null && (! $variant->is_active || $variant->product_id !== $product->id)) {
            throw ValidationException::withMessages(['variant_id' => 'Choose a valid product option.']);
        }

        if ($quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Choose at least one item.']);
        }

        return DB::transaction(function () use ($request, $product, $variant, $quantity): Cart {
            $cart = $this->current($request, true);
            $item = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->where('variant_id', $variant?->id)
                ->first();

            $existing = $item !== null ? $item->quantity : 0;
            $requested = $existing + $quantity;
            $this->assertStock($product, $variant, $requested);

            $price = $variant !== null ? $variant->effective_price : (float) $product->price;

            if ($item !== null) {
                $item->update(['quantity' => $requested, 'price' => $price]);
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'quantity' => $quantity,
                    'price' => $price,
                ]);
            }

            $cart->update(['expires_at' => now()->addMinutes((int) config('cart.token_expiry', 1440))]);

            return $cart->refresh();
        });
    }

    public function update(Request $request, CartItem $item, int $quantity): Cart
    {
        if ($quantity < 1) {
            return $this->remove($request, $item);
        }

        $product = $item->product;
        $variant = $item->variant;

        if ($product === null || ! $product->is_active) {
            throw ValidationException::withMessages(['quantity' => 'This product is no longer available.']);
        }

        if ($variant !== null && (! $variant->is_active || $variant->product_id !== $product->id)) {
            throw ValidationException::withMessages(['quantity' => 'This product option is no longer available.']);
        }

        $this->assertStock($product, $variant, $quantity);

        $item->update([
            'quantity' => $quantity,
            'price' => $variant !== null ? $variant->effective_price : (float) $product->price,
        ]);

        return $item->cart->refresh();
    }

    public function remove(Request $request, CartItem $item): Cart
    {
        $cart = $item->cart;
        $item->delete();

        return $cart->refresh();
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }

    /**
     * @return array<int, string>
     */
    public function revalidate(Cart $cart): array
    {
        $messages = [];
        $cart->load(['items.product', 'items.variant']);

        foreach ($cart->items as $item) {
            $product = $item->product;
            $variant = $item->variant;

            if ($product === null || ! $product->is_active) {
                $item->delete();
                $messages[] = 'An unavailable product was removed from your cart.';

                continue;
            }

            if ($variant !== null && (! $variant->is_active || $variant->product_id !== $product->id)) {
                $item->delete();
                $messages[] = 'An unavailable product option was removed from your cart.';

                continue;
            }

            $tracked = $variant !== null ? $variant->track_inventory : $product->track_inventory;
            $available = $variant !== null ? $variant->stock_quantity : $product->stock_quantity;

            if ($tracked && $available <= 0) {
                $item->delete();
                $messages[] = $product->name.' is out of stock and was removed from your cart.';

                continue;
            }

            if ($tracked && $item->quantity > $available) {
                $item->update(['quantity' => $available]);
                $messages[] = 'Quantity for '.$product->name.' was reduced to the available stock.';
            }

            $price = $variant !== null ? $variant->effective_price : (float) $product->price;

            if ((float) $item->price !== $price) {
                $item->update(['price' => $price]);
                $messages[] = 'The price of '.$product->name.' was updated.';
            }
        }

        return $messages;
    }

    /**
     * @return array{subtotal: float, item_count: int}
     */
    public function summary(Cart $cart): array
    {
        $cart->loadMissing('items');

        return [
            'subtotal' => (float) $cart->items->sum(fn (CartItem $item): float => (float) $item->price * $item->quantity),
            'item_count' => (int) $cart->items->sum('quantity'),
        ];
    }

    public function count(Request $request): int
    {
        $cart = $this->current($request);

        return $cart !== null ? $cart->item_count : 0;
    }

    private function assertStock(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        $tracked = $variant !== null ? $variant->track_inventory : $product->track_inventory;

        if (! $tracked) {
            return;
        }

        $available = $variant !== null ? $variant->stock_quantity : $product->stock_quantity;

        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => 'Only '.$available.' of '.$product->name.' are available.',
            ]);
        }
    }

    private function remember(Request $request, Cart $cart): void
    {
        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $cart->token);
        }
    }
}
