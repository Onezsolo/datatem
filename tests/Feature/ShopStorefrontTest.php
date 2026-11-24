<?php

namespace Tests\Feature;

use App\Models\Affiliate;
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

    public function test_shop_home_lists_seeded_catalogue_publicly(): void
    {
        $this->get('/shop')
            ->assertOk()
            ->assertSee('Shop by category')
            ->assertSee('Samsung Galaxy A56')
            ->assertSee('Primary navigation')
            ->assertDontSee('Member navigation');
    }

    public function test_product_page_renders_publicly(): void
    {
        $this->get('/shop/product/samsung-galaxy-a56')
            ->assertOk()
            ->assertSee('Samsung Galaxy A56')
            ->assertSee('Sign in to buy')
            ->assertDontSee('Add to cart', false);
    }

    public function test_guest_can_browse_but_cannot_cart_or_checkout(): void
    {
        $this->get('/shop')->assertOk();
        $this->get('/shop/product/samsung-galaxy-a56')->assertOk();

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

    public function test_logged_in_shop_uses_member_sidebar(): void
    {
        $this->actingAs($this->shopper());

        $this->get('/shop')
            ->assertOk()
            ->assertSee('Member navigation');
    }

    public function test_tracking_redirects_to_the_users_own_shop_orders(): void
    {
        $this->get('/shop/track')->assertRedirectContains('/login');

        $this->actingAs($this->shopper())
            ->get('/shop/track')
            ->assertRedirect(route('orders.shop'));
    }

    public function test_more_page_renders_publicly(): void
    {
        $this->get('/shop/more')->assertOk();
    }

    public function test_shop_purchase_does_not_create_reseller_or_referral_earnings(): void
    {
        $referrer = User::create([
            'full_name' => 'Referrer',
            'email' => 'referrer@example.com',
            'phone' => '0244000050',
            'password' => bcrypt('password'),
        ]);

        $affiliate = Affiliate::create([
            'user_id' => $referrer->id,
            'referral_code' => 'REFTEST1',
            'status' => 'approved',
            'commission_rate' => 5,
            'balance' => 10,
            'total_earned' => 20,
        ]);

        $user = $this->shopper(8000.0);
        $user->forceFill([
            'reseller_earnings_balance' => 12.5,
            'referred_by_affiliate_id' => $affiliate->id,
        ])->save();

        $this->actingAs($user);

        $product = Product::query()->where('slug', 'samsung-galaxy-a56')->firstOrFail();

        $this->post('/shop/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertRedirect();

        $this->post('/shop/checkout', [
            'customer_name' => 'Ama Mensah',
            'customer_phone' => '0244000000',
            'delivery_address' => '12 Independence Avenue',
            'delivery_city' => 'Accra',
            'delivery_zone' => 'accra',
        ])->assertRedirect();

        $order = Order::query()->latest('id')->firstOrFail();

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('12.50', (string) $user->fresh()->reseller_earnings_balance);
        $this->assertSame('10.00', (string) $affiliate->fresh()->balance);
        $this->assertSame('20.00', (string) $affiliate->fresh()->total_earned);
    }

    public function test_product_page_shows_the_primary_photo(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $product = Product::query()->where('slug', 'samsung-galaxy-a56')->firstOrFail();

        \Illuminate\Support\Facades\Storage::disk('public')->put('product-images/samsung.jpg', 'fake');

        $image = \App\Shop\Models\ProductImage::create([
            'product_id' => $product->id,
            'path' => 'product-images/samsung.jpg',
            'alt_text' => 'Samsung Galaxy A56',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $this->get('/shop/product/samsung-galaxy-a56')
            ->assertOk()
            ->assertSee('storage/'.$image->path, false);
    }
}
