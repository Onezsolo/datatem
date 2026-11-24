<?php

declare(strict_types=1);

namespace App\Shop\Services;

use App\Shop\Models\Cart;
use App\Shop\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function placeOrder(Cart $cart, array $data): Order
    {
        $zone = (string) ($data['delivery_zone'] ?? config('delivery.default_zone'));

        if (! array_key_exists($zone, config('delivery.zones'))) {
            throw ValidationException::withMessages(['delivery_zone' => 'Choose a valid delivery zone.']);
        }

        return DB::transaction(function () use ($cart, $data, $zone): Order {
            DB::table('shop_carts')->where('id', $cart->id)->lockForUpdate()->first();

            $lockedCart = Cart::query()->whereKey($cart->id)->first();

            if ($lockedCart === null) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            $lockedCart->load(['items.product', 'items.variant']);

            if ($lockedCart->items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            $subtotal = 0.0;
            $orderItems = [];

            foreach ($lockedCart->items as $item) {
                $product = $item->product;
                $variant = $item->variant;

                if ($product === null || ! $product->is_active) {
                    throw ValidationException::withMessages(['cart' => 'A cart item is no longer available.']);
                }

                if ($variant !== null && (! $variant->is_active || $variant->product_id !== $product->id)) {
                    throw ValidationException::withMessages(['cart' => 'A cart item option is no longer available.']);
                }

                $tracked = $variant !== null ? $variant->track_inventory : $product->track_inventory;
                $available = $variant !== null ? $variant->stock_quantity : $product->stock_quantity;

                if ($tracked && $item->quantity > $available) {
                    throw ValidationException::withMessages([
                        'cart' => 'Insufficient stock for '.$product->name.'.',
                    ]);
                }

                $unitPrice = $variant !== null ? $variant->effective_price : (float) $product->price;
                $lineTotal = round($unitPrice * $item->quantity, 2);
                $subtotal += $lineTotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'variant_name' => $variant?->name,
                    'product_sku' => $variant !== null ? $variant->sku : $product->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                    'product_snapshot' => [
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'sku' => $product->sku,
                        'price' => (float) $product->price,
                        'variant' => $variant?->name,
                        'variant_sku' => $variant?->sku,
                    ],
                ];
            }

            $deliveryFee = (float) config('delivery.zones.'.$zone.'.fee', 0);
            $serviceCharge = (float) config('delivery.service_charge', 0);
            $total = round($subtotal + $deliveryFee + $serviceCharge, 2);

            $order = Order::create([
                'user_id' => $data['user_id'] ?? null,
                'customer_name' => (string) $data['customer_name'],
                'customer_phone' => $this->normalisePhone((string) $data['customer_phone']),
                'customer_email' => $data['customer_email'] ?? null,
                'delivery_address' => (string) $data['delivery_address'],
                'delivery_city' => (string) $data['delivery_city'],
                'delivery_region' => $data['delivery_region'] ?? null,
                'delivery_zone' => $zone,
                'delivery_phone' => isset($data['delivery_phone']) ? $this->normalisePhone((string) $data['delivery_phone']) : null,
                'delivery_notes' => $data['delivery_notes'] ?? null,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'service_charge' => $serviceCharge,
                'discount' => 0,
                'total' => $total,
                'currency' => (string) config('store.currency', 'GHS'),
                'payment_method' => 'paystack',
                'status' => Order::STATUS_PENDING,
                'payment_status' => 'pending',
                'meta' => ['delivery_zone_label' => config('delivery.zones.'.$zone.'.label')],
            ]);

            foreach ($orderItems as $row) {
                $order->items()->create($row);
            }

            app(InventoryService::class)->reserve($order);

            $lockedCart->items()->delete();
            $lockedCart->delete();

            return $order->load('items');
        });
    }

    public function normalisePhone(string $phone): string
    {
        return preg_replace('/[\s\-]+/', '', trim($phone)) ?? '';
    }
}
