<?php

namespace Tests\Feature;

use App\Models\User;
use App\Shop\Models\Order;
use App\Shop\Models\Payment;
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

    private function shopper(float $balance = 8000.0): User
    {
        return User::create([
            'full_name' => 'Ama Mensah',
            'email' => 'ama-shop@example.com',
            'phone' => '0244000000',
            'balance' => $balance,
            'password' => bcrypt('password'),
        ]);
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

    public function test_guest_cannot_add_to_cart_or_open_checkout(): void
    {
        $product = Product::query()->where('slug', 'samsung-galaxy-a56')->firstOrFail();

        $this->post('/shop/cart/items', ['product_id' => $product->id, 'quantity' => 1])
            ->assertRedirectContains('/login');

        $this->get('/shop/cart')->assertRedirectContains('/login');
        $this->get('/shop/checkout')->assertRedirectContains('/login');
    }

    public function test_checkout_pays_from_wallet_and_saves_delivery_details(): void
    {
        $user = $this->shopper(8000.0);
        $this->actingAs($user);

        $product = Product::query()->where('slug', 'samsung-galaxy-a56')->firstOrFail();

        $this->post('/shop/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->get('/shop/cart')->assertOk()->assertSee('Samsung Galaxy A56');
        $this->get('/shop/checkout')->assertOk()->assertSee('Wallet balance');

        $response = $this->post('/shop/checkout', [
            'customer_name' => 'Ama Mensah',
            'customer_phone' => '0244000000',
            'delivery_address' => '12 Independence Avenue',
            'delivery_city' => 'Accra',
            'delivery_region' => 'Greater Accra',
            'delivery_zone' => 'accra',
            'delivery_notes' => 'Call on arrival',
        ]);

        $response->assertRedirect();

        $order = Order::query()->latest('id')->firstOrFail();

        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(Order::STATUS_PAID, $order->status);
        $this->assertSame('wallet', $order->payment_method);
        $this->assertSame(7023.0, (float) $order->total);
        $this->assertCount(1, $order->items);
        $this->assertSame(2, $order->items->first()->quantity);

        $this->assertSame(977.0, (float) $user->fresh()->balance);
        $this->assertSame(
            (float) $product->stock_quantity - 2,
            (float) $product->fresh()->stock_quantity
        );

        $payment = Payment::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame('paid', $payment->status);
        $this->assertSame('wallet', $payment->payment_method);
        $this->assertSame(7023.0, (float) $payment->amount);

        $user->refresh();
        $this->assertSame('12 Independence Avenue', $user->delivery_address);
        $this->assertSame('Accra', $user->delivery_city);
        $this->assertSame('accra', $user->delivery_zone);
        $this->assertSame('Call on arrival', $user->delivery_notes);
    }

    public function test_checkout_is_blocked_until_wallet_is_topped_up(): void
    {
        $user = $this->shopper(100.0);
        $this->actingAs($user);

        $product = Product::query()->where('slug', 'samsung-galaxy-a56')->firstOrFail();
        $stockBefore = (float) $product->stock_quantity;

        $this->post('/shop/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertRedirect();

        $this->post('/shop/checkout', [
            'customer_name' => 'Ama Mensah',
            'customer_phone' => '0244000000',
            'delivery_address' => '12 Independence Avenue',
            'delivery_city' => 'Accra',
            'delivery_zone' => 'accra',
        ])->assertSessionHasErrors('wallet');

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, Payment::query()->count());
        $this->assertSame($stockBefore, (float) $product->fresh()->stock_quantity);
        $this->assertSame(100.0, (float) $user->fresh()->balance);
    }

    public function test_track_form_and_more_page_render(): void
    {
        $this->get('/shop/track')->assertOk()->assertSee('Track an order');
        $this->get('/shop/more')->assertOk();
    }
}
