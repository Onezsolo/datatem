<?php

namespace Tests\Feature;

use App\Shop\Models\Order;
use App\Shop\Models\Product;
use Database\Seeders\ShopCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopStorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ShopCatalogSeeder::class);
    }

    public function test_shop_home_lists_seeded_catalogue(): void
    {
        $this->get('/shop')
            ->assertOk()
            ->assertSee('Shop by category')
            ->assertSee('Samsung Galaxy A56');
    }

    public function test_product_page_renders(): void
    {
        $this->get('/shop/product/samsung-galaxy-a56')
            ->assertOk()
            ->assertSee('Samsung Galaxy A56')
            ->assertSee('Add to cart');
    }

    public function test_cart_add_then_checkout_creates_paid_pending_order(): void
    {
        $product = Product::query()->where('slug', 'samsung-galaxy-a56')->firstOrFail();

        $this->post('/shop/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->get('/shop/cart')
            ->assertOk()
            ->assertSee('Samsung Galaxy A56');

        $this->get('/shop/checkout')->assertOk();

        $response = $this->post('/shop/checkout', [
            'customer_name' => 'Ama Mensah',
            'customer_phone' => '0244000000',
            'delivery_address' => '12 Independence Avenue',
            'delivery_city' => 'Accra',
            'delivery_zone' => 'accra',
            'payment_method' => 'paystack',
        ]);

        $order = Order::query()->latest('id')->first();

        $this->assertNotNull($order);
        $response->assertRedirect();

        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('GHS', $order->currency);
        $this->assertSame(3499.00 * 2 + 25.00, (float) $order->total);
        $this->assertCount(1, $order->items);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame(
            (float) $product->stock_quantity - 2,
            (float) $product->fresh()->stock_quantity
        );
    }

    public function test_track_form_and_more_page_render(): void
    {
        $this->get('/shop/track')->assertOk()->assertSee('Track an order');
        $this->get('/shop/more')->assertOk();
    }
}
