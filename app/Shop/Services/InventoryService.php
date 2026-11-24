<?php

declare(strict_types=1);

namespace App\Shop\Services;

use App\Shop\Models\InventoryMovement;
use App\Shop\Models\Order;
use App\Shop\Models\OrderItem;
use App\Shop\Models\Product;
use App\Shop\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function reserve(Order $order): void
    {
        foreach (OrderItem::query()->where('order_id', $order->id)->get() as $item) {
            $this->reserveItem($order, $item);
        }
    }

    public function release(Order $order): void
    {
        foreach (OrderItem::query()->where('order_id', $order->id)->get() as $item) {
            $this->releaseItem($order, $item);
        }
    }

    public function adjust(
        Product $product,
        ?ProductVariant $variant,
        int $quantity,
        string $type,
        ?string $reason = null,
        ?string $reference = null,
    ): InventoryMovement {
        return DB::transaction(function () use ($product, $variant, $quantity, $type, $reason, $reference): InventoryMovement {
            DB::table('shop_products')->where('id', $product->id)->lockForUpdate()->first();

            $lockedProduct = Product::query()->whereKey($product->id)->first();
            $lockedVariant = null;

            if ($variant !== null) {
                DB::table('shop_product_variants')->where('id', $variant->id)->lockForUpdate()->first();
                $lockedVariant = ProductVariant::query()->whereKey($variant->id)->first();
            }

            if ($lockedProduct === null) {
                throw ValidationException::withMessages(['product_id' => 'The product was not found.']);
            }

            if ($variant !== null && $lockedVariant === null) {
                throw ValidationException::withMessages(['variant_id' => 'The variant was not found.']);
            }

            if ($lockedVariant !== null && $lockedVariant->product_id !== $lockedProduct->id) {
                throw ValidationException::withMessages(['variant_id' => 'The variant does not belong to this product.']);
            }

            $current = $lockedVariant !== null ? $lockedVariant->stock_quantity : $lockedProduct->stock_quantity;
            $next = $current + $quantity;

            if ($next < 0) {
                throw ValidationException::withMessages(['quantity' => 'Stock cannot be reduced below zero.']);
            }

            if ($lockedVariant !== null) {
                $lockedVariant->update(['stock_quantity' => $next]);
            } else {
                $lockedProduct->update(['stock_quantity' => $next]);
            }

            return InventoryMovement::create([
                'product_id' => $lockedProduct->id,
                'variant_id' => $lockedVariant?->id,
                'type' => $type,
                'quantity' => $quantity,
                'reason' => $reason,
                'reference' => $reference,
            ]);
        });
    }

    private function reserveItem(Order $order, OrderItem $item): void
    {
        DB::table('shop_products')->where('id', $item->product_id)->lockForUpdate()->first();
        $product = Product::query()->whereKey($item->product_id)->first();
        $variant = null;

        if ($item->variant_id !== null) {
            DB::table('shop_product_variants')->where('id', $item->variant_id)->lockForUpdate()->first();
            $variant = ProductVariant::query()->whereKey($item->variant_id)->first();
        }

        if ($product === null || ($variant !== null && $variant->product_id !== $product->id)) {
            throw ValidationException::withMessages(['cart' => 'A cart item is no longer available.']);
        }

        $tracked = $variant !== null ? $variant->track_inventory : $product->track_inventory;
        $available = $variant !== null ? $variant->stock_quantity : $product->stock_quantity;

        if ($tracked && $available < $item->quantity) {
            throw ValidationException::withMessages([
                'cart' => 'Insufficient stock for '.$item->product_name.'.',
            ]);
        }

        if ($tracked) {
            if ($variant !== null) {
                $variant->update(['stock_quantity' => $available - $item->quantity]);
            } else {
                $product->update(['stock_quantity' => $available - $item->quantity]);
            }
        }

        InventoryMovement::create([
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'type' => InventoryMovement::TYPE_SALE,
            'quantity' => -$item->quantity,
            'reason' => 'Order reservation',
            'reference' => $order->order_number,
            'meta' => ['order_item_id' => $item->id],
        ]);
    }

    private function releaseItem(Order $order, OrderItem $item): void
    {
        DB::table('shop_products')->where('id', $item->product_id)->lockForUpdate()->first();
        $product = Product::query()->whereKey($item->product_id)->first();

        if ($product === null) {
            return;
        }

        $variant = null;

        if ($item->variant_id !== null) {
            DB::table('shop_product_variants')->where('id', $item->variant_id)->lockForUpdate()->first();
            $variant = ProductVariant::query()->whereKey($item->variant_id)->first();
        }

        $tracked = $variant !== null ? $variant->track_inventory : $product->track_inventory;

        if ($tracked) {
            if ($variant !== null) {
                $variant->update(['stock_quantity' => $variant->stock_quantity + $item->quantity]);
            } else {
                $product->update(['stock_quantity' => $product->stock_quantity + $item->quantity]);
            }
        }

        InventoryMovement::create([
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'type' => InventoryMovement::TYPE_RETURN,
            'quantity' => $item->quantity,
            'reason' => 'Order cancellation',
            'reference' => $order->order_number,
            'meta' => ['order_item_id' => $item->id],
        ]);
    }
}
