<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ServiceOrder;
use App\Shop\Models\Order;
use App\Shop\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $admin = User::create([
            'full_name' => 'Finance Admin',
            'email' => 'finance-admin@example.com',
            'phone' => '0244000003',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        return $this->postJson('/api/auth/login', [
            'login' => $admin->email,
            'password' => 'password',
        ])->assertOk()->json('access_token');
    }

    public function test_finance_statistics_include_shop_revenue_in_the_same_dashboard(): void
    {
        $token = $this->adminToken();

        $order = Order::create([
            'order_number' => 'SHOP-FIN-1',
            'customer_name' => 'Ama Mensah',
            'customer_phone' => '0244000000',
            'delivery_address' => '12 Independence Ave',
            'delivery_city' => 'Accra',
            'subtotal' => 125,
            'delivery_fee' => 25,
            'total' => 150,
            'payment_status' => 'paid',
            'status' => 'paid',
        ]);

        Payment::create([
            'order_id' => $order->id,
            'reference' => 'finance-ref-1',
            'amount' => 150,
            'status' => 'paid',
        ]);

        ServiceOrder::create([
            'user_id' => null,
            'reference' => 'SVC-FIN-1',
            'remote_type' => 'data_bundle',
            'amount' => 200,
            'status' => 'successful',
            'payment_status' => 'paid',
            'wallet_debited' => true,
        ]);

        ServiceOrder::create([
            'user_id' => null,
            'reference' => 'SVC-FIN-2',
            'remote_type' => 'utility',
            'amount' => 30,
            'status' => 'failed',
            'payment_status' => 'refunded',
            'wallet_debited' => false,
        ]);
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/finance-statistics')
            ->assertOk()
            ->assertJsonPath('shop_revenue', 150)
            ->assertJsonPath('shop_paid_orders', 1)
            ->assertJsonPath('shop_delivery_fees', 25)
            ->assertJsonPath('shop_pending_orders', 0)
            ->assertJsonPath('service_revenue', 200)
            ->assertJsonPath('service_paid_orders', 1)
            ->assertJsonPath('service_refunded_orders', 1);

        $this->get('/admin/statistics')
            ->assertOk()
            ->assertSee('service finance')
            ->assertSee('Service revenue')
            ->assertSee('Combined revenue');
    }
}
