<?php

namespace Tests\Feature;

use App\Models\User;
use App\Shop\Models\Category;
use App\Shop\Models\Order;
use App\Shop\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $admin = User::create([
            'full_name' => 'Admin User',
            'email' => 'admin-shop@example.com',
            'phone' => '0244000001',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertOk();

        return $response->json('access_token');
    }

    public function test_admin_shop_page_renders(): void
    {
        $this->get('/admin/shop')
            ->assertOk()
            ->assertSee('Shop catalog')
            ->assertSee('Add product');
    }

    public function test_shop_admin_api_requires_authentication(): void
    {
        $this->getJson('/api/admin/shop/products')->assertUnauthorized();
    }

    public function test_admin_can_manage_categories_and_products(): void
    {
        $token = $this->adminToken();
        $headers = ['Authorization' => 'Bearer '.$token];

        $category = $this->withHeaders($headers)
            ->postJson('/api/admin/shop/categories', ['name' => 'Gadgets'])
            ->assertCreated()
            ->json('category');

        $this->assertSame('gadgets', $category['slug']);

        $product = $this->withHeaders($headers)
            ->postJson('/api/admin/shop/products', [
                'category_id' => $category['id'],
                'name' => 'Smart Speaker',
                'price' => 250,
                'sku' => 'DT-GAD-SPK1',
                'stock_quantity' => 10,
            ])
            ->assertCreated()
            ->json('product');

        $this->assertSame(10, $product['stock_quantity']);
        $this->assertSame('shop_products', (new Product)->getTable());
        $this->assertSame(1, Product::query()->count());

        $this->withHeaders($headers)
            ->postJson('/api/admin/shop/products/'.$product['id'].'/stock', [
                'quantity' => 5,
                'reason' => 'Restock',
            ])
            ->assertOk()
            ->assertJson(['stock_quantity' => 15]);

        $this->withHeaders($headers)
            ->deleteJson('/api/admin/shop/products/'.$product['id'])
            ->assertOk();

        $this->assertSame(0, Product::query()->count());

        $this->withHeaders($headers)
            ->deleteJson('/api/admin/shop/categories/'.$category['id'])
            ->assertOk();

        $this->assertSame(0, Category::query()->count());
    }

    public function test_admin_can_cancel_a_pending_shop_order(): void
    {
        $token = $this->adminToken();
        $headers = ['Authorization' => 'Bearer '.$token];

        $order = Order::create([
            'order_number' => 'SHOP-ADMIN-1',
            'customer_name' => 'Ama Mensah',
            'customer_phone' => '0244000000',
            'delivery_address' => '12 Independence Ave',
            'delivery_city' => 'Accra',
            'subtotal' => 100,
            'total' => 100,
        ]);

        $this->withHeaders($headers)
            ->getJson('/api/admin/shop/orders')
            ->assertOk()
            ->assertJsonPath('data.0.order_number', 'SHOP-ADMIN-1');

        $this->withHeaders($headers)
            ->putJson('/api/admin/shop/orders/'.$order->id.'/status', ['status' => 'cancelled'])
            ->assertOk();

        $this->assertSame('cancelled', $order->fresh()->status);
    }
}
