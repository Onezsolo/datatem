<?php

namespace Tests\Feature;

use App\Shop\Models\Cart;
use App\Shop\Models\CartItem;
use App\Shop\Models\Category;
use App\Shop\Models\Order;
use App\Shop\Models\OrderItem;
use App\Shop\Models\Payment;
use App\Shop\Models\Product;
use App\Shop\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_tables_are_prefixed_and_migrations_run_on_sqlite(): void
    {
        $this->assertSame('shop_categories', (new Category)->getTable());
        $this->assertSame('shop_products', (new Product)->getTable());
        $this->assertSame('shop_carts', (new Cart)->getTable());
        $this->assertSame('shop_orders', (new Order)->getTable());
        $this->assertSame('shop_payments', (new Payment)->getTable());
    }

    public function test_catalog_cart_and_order_relations_work(): void
    {
        $category = Category::create(['name' => 'Phones', 'slug' => 'phones']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Phone X',
            'slug' => 'phone-x',
            'price' => 499.00,
            'sku' => 'PHX-1',
            'stock_quantity' => 10,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Black',
            'sku' => 'PHX-1-BK',
            'price' => 499.00,
            'stock_quantity' => 5,
        ]);

        $cart = Cart::create(['token' => str_repeat('a', 40)]);
        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
            'price' => 499.00,
        ]);

        $order = Order::create([
            'order_number' => 'SHOP-TEST-1',
            'customer_name' => 'Ama Mensah',
            'customer_phone' => '0240000000',
            'delivery_address' => '12 Independence Ave',
            'delivery_city' => 'Accra',
            'subtotal' => 998.00,
            'total' => 998.00,
        ]);
        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => 2,
            'unit_price' => 499.00,
            'total_price' => 998.00,
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'reference' => 'ref-test-1',
            'amount' => 998.00,
        ]);

        $this->assertCount(1, $category->products);
        $this->assertCount(1, $product->variants);
        $this->assertCount(1, $cart->items);
        $this->assertTrue($cart->items->first()->is($item));
        $this->assertCount(1, $order->items);
        $this->assertTrue($order->items->first()->is($orderItem));
        $this->assertCount(1, $order->payments);
        $this->assertTrue($order->payments->first()->is($payment));
        $this->assertSame('pending', $order->status);
    }
}
