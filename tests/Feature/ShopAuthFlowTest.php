<?php

namespace Tests\Feature;

use App\Models\User;
use App\Shop\Models\Order;
use App\Shop\Models\Product;
use Database\Seeders\ShopCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ShopCatalogSeeder::class);
    }

    public function test_shop_uses_r2b_login_cookie_for_cart_checkout_and_order_ownership(): void
    {
        $user = User::create([
            'full_name' => 'Cookie Shopper',
            'email' => 'cookie-shopper@example.com',
            'phone' => '0244000009',
            'balance' => 5000,
            'password' => bcrypt('password'),
        ]);

        $login = $this->postJson('/api/auth/login', [
            'login' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $authCookie = collect($login->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === 'auth_token');

        $this->assertNotNull($authCookie);

        $product = Product::query()->where('slug', 'samsung-galaxy-a56')->firstOrFail();

        $this->withCookie('auth_token', $authCookie->getValue())
            ->post('/shop/cart/items', ['product_id' => $product->id, 'quantity' => 1])
            ->assertRedirect();

        $this->withCookie('auth_token', $authCookie->getValue())
            ->get('/shop/checkout')
            ->assertOk();

        $this->withCookie('auth_token', $authCookie->getValue())
            ->post('/shop/checkout', [
                'customer_name' => $user->full_name,
                'customer_phone' => $user->phone,
                'customer_email' => $user->email,
                'delivery_address' => '12 Independence Avenue',
                'delivery_city' => 'Accra',
                'delivery_zone' => 'accra',
                'payment_method' => 'wallet',
            ])
            ->assertRedirect();

        $order = Order::query()->latest('id')->firstOrFail();

        $this->assertSame($user->id, $order->user_id);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('wallet', $order->payment_method);
        $this->assertSame(5000.0 - (float) $order->total, (float) $user->fresh()->balance);
        $this->assertSame('12 Independence Avenue', $user->fresh()->delivery_address);
    }

    public function test_guest_is_redirected_to_r2b_login_before_checkout(): void
    {
        $this->get('/shop/checkout')->assertRedirectContains('/login');
    }
}
