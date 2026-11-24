<?php

namespace Tests\Feature;

use App\Http\Controllers\Shop\MemberOrderController;
use App\Models\User;
use App\Shop\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ShopOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_orders_page_renders_in_member_shell(): void
    {
        $this->get('/orders/shop')
            ->assertOk()
            ->assertSee('Shop orders')
            ->assertSee('Member navigation');
    }

    public function test_member_api_matches_shop_orders_by_phone_or_email(): void
    {
        $user = User::create([
            'full_name' => 'Ama Mensah',
            'email' => 'ama@example.com',
            'phone' => '0244000000',
            'password' => bcrypt('password'),
        ]);

        $matching = Order::create([
            'order_number' => 'SHOP-MATCH-1',
            'customer_name' => 'Ama Mensah',
            'customer_phone' => '0244000000',
            'delivery_address' => '12 Independence Ave',
            'delivery_city' => 'Accra',
            'subtotal' => 100,
            'total' => 100,
        ]);

        Order::create([
            'order_number' => 'SHOP-OTHER-1',
            'customer_name' => 'Other Buyer',
            'customer_phone' => '0200000000',
            'delivery_address' => 'Somewhere',
            'delivery_city' => 'Kumasi',
            'subtotal' => 50,
            'total' => 50,
        ]);

        $request = Request::create('/api/auth/shop-orders');
        $request->setUserResolver(fn () => $user);

        $payload = app(MemberOrderController::class)->index($request)->getData(true);

        $this->assertCount(1, $payload['orders']);
        $this->assertSame($matching->order_number, $payload['orders'][0]['order_number']);
        $this->assertStringContainsString('/shop/orders/', $payload['orders'][0]['view_url']);
    }
}
