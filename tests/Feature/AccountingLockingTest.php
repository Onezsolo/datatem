<?php

namespace Tests\Feature;

use App\Models\PatupaaCatalogItem;
use App\Models\ServiceOrder;
use App\Models\ServiceProvider;
use App\Models\Topup;
use App\Models\User;
use App\Shop\Models\Order;
use App\Shop\Models\Product;
use Database\Seeders\ShopCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AccountingLockingTest extends TestCase
{
    use RefreshDatabase;

    private function member(float $balance = 10000): User
    {
        return User::create([
            'full_name' => 'Ledger User',
            'email' => 'ledger-'.uniqid().'@example.com',
            'phone' => '0244000100',
            'balance' => $balance,
            'password' => bcrypt('password'),
        ]);
    }

    public function test_replayed_shop_checkout_token_creates_only_one_order_and_one_debit(): void
    {
        $this->seed(ShopCatalogSeeder::class);

        $user = $this->member();
        $this->actingAs($user);

        $product = Product::query()->where('slug', 'samsung-galaxy-a56')->firstOrFail();

        $this->post('/shop/cart/items', ['product_id' => $product->id, 'quantity' => 1])->assertRedirect();

        $payload = [
            'customer_name' => 'Ledger User',
            'customer_phone' => '0244000100',
            'delivery_address' => '1 Ledger Street',
            'delivery_city' => 'Accra',
            'delivery_zone' => 'accra',
            'checkout_token' => 'checkout-replay-token-1',
        ];

        $first = $this->post('/shop/checkout', $payload);
        $first->assertRedirect();

        $order = Order::query()->latest('id')->firstOrFail();
        $balanceAfterFirst = (float) $user->fresh()->balance;

        $replay = $this->post('/shop/checkout', $payload);
        $replay->assertRedirect();

        $this->assertSame(1, Order::query()->count());
        $this->assertSame($balanceAfterFirst, (float) $user->fresh()->balance);
        $this->assertStringContainsString($order->order_number, $first->headers->get('Location'));
        $this->assertStringContainsString($order->order_number, $replay->headers->get('Location'));
    }

    public function test_replayed_service_request_token_creates_only_one_order_and_one_debit(): void
    {
        config([
            'services.patupaa.base_url' => 'https://patupaa.test/api/v1',
            'services.patupaa.api_key' => 'pk_test',
            'services.patupaa.api_secret' => 'sk_test',
        ]);

        Http::fake(fn () => Http::response(['success' => true, 'data' => [
            'reference' => 'API-DUP-1',
            'order_status' => 'processing',
        ]], 201));

        $user = $this->member(1000);
        $this->actingAs($user);

        $provider = ServiceProvider::create([
            'name' => 'Lock Source',
            'driver' => 'patupaa',
            'base_url' => 'https://patupaa.test/api/v1',
            'api_key' => 'pk_test',
            'api_secret' => 'sk_test',
            'services' => ['data_bundle', 'digital', 'waec', 'utility', 'afa'],
            'is_active' => true,
            'is_default' => true,
        ]);

        $item = PatupaaCatalogItem::create([
            'provider_id' => $provider->id,
            'remote_type' => 'data_bundle',
            'remote_id' => '1',
            'plan_code' => 'MTN-1GB',
            'name' => 'MTN 1GB',
            'price' => 5,
            'remote_enabled' => true,
            'admin_enabled' => true,
        ]);

        $payload = ['beneficiary' => '0244000100', 'request_token' => 'service-replay-token-1'];

        $first = $this->post('/services/data/'.$item->id, $payload);
        $first->assertRedirect();

        $order = ServiceOrder::query()->latest('id')->firstOrFail();
        $balanceAfterFirst = (float) $user->fresh()->balance;

        $replay = $this->post('/services/data/'.$item->id, $payload);
        $replay->assertRedirect();

        $this->assertSame(1, ServiceOrder::query()->count());
        $this->assertSame(995.0, $balanceAfterFirst);
        $this->assertSame($balanceAfterFirst, (float) $user->fresh()->balance);
        $this->assertStringContainsString($order->reference, $finish = $replay->headers->get('Location'));
    }

    public function test_topup_approval_twice_credits_the_wallet_once(): void
    {
        $admin = User::create([
            'full_name' => 'Topup Admin',
            'email' => 'topup-admin@example.com',
            'phone' => '0244000101',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $user = $this->member(0);

        $topup = Topup::create([
            'id' => 'TUP-LOCK-TEST-1',
            'user_id' => $user->id,
            'amount' => 50,
            'method' => 'manual',
            'status' => 'pending',
        ]);

        $token = $this->postJson('/api/auth/login', [
            'login' => $admin->email,
            'password' => 'password',
        ])->assertOk()->json('access_token');

        $headers = ['Authorization' => 'Bearer '.$token];

        $this->withHeaders($headers)
            ->postJson('/api/admin/topups/'.$topup->id.'/approve')
            ->assertOk();

        $this->withHeaders($headers)
            ->postJson('/api/admin/topups/'.$topup->id.'/approve')
            ->assertStatus(400);

        $this->assertSame('approved', $topup->fresh()->status);
        $this->assertSame(50.0, (float) $user->fresh()->balance);
    }
}
