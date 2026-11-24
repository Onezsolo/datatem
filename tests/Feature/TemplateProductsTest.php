<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FeatureSettings;
use App\Services\Patupaa\PatupaaSettings;
use App\Services\TemplateProductService;
use App\Services\ThemeSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateProductsTest extends TestCase
{
    use RefreshDatabase;

    private function developer(): User
    {
        return User::create([
            'full_name' => 'Product Dev',
            'email' => 'product-dev-'.uniqid().'@example.com',
            'phone' => '05'.random_int(100000000, 999999999),
            'password' => bcrypt('password'),
            'role' => User::ROLE_DEVELOPER,
        ]);
    }

    public function test_five_template_products_are_available(): void
    {
        $this->assertCount(5, TemplateProductService::variants());
        $this->assertSame([
            'data-subscription-mall',
            'data-subscription',
            'mall-only',
            'subscription-only',
            'data-only',
        ], array_keys(TemplateProductService::variants()));

        $this->actingAs($this->developer())
            ->get('/developer/products')
            ->assertOk()
            ->assertSee('Data + Subscriptions + Mall')
            ->assertSee('Mall only')
            ->assertSee('Subscriptions only')
            ->assertSee('Data only');
    }

    public function test_mall_only_product_configures_shop_only_template(): void
    {
        $this->actingAs($this->developer())
            ->post('/developer/products/mall-only/apply')
            ->assertRedirect(route('developer.products'));

        $this->assertTrue(FeatureSettings::enabled('shop'));
        $this->assertTrue(FeatureSettings::enabled('shop_orders'));
        $this->assertTrue(FeatureSettings::enabled('wallet'));
        $this->assertFalse(FeatureSettings::enabled('data'));
        $this->assertFalse(FeatureSettings::enabled('subscriptions'));
        $this->assertFalse(PatupaaSettings::enabled('data_bundle'));
        $this->assertFalse(PatupaaSettings::enabled('digital'));
        $this->assertSame('ocean-steel', ThemeSettings::currentKey());
        $this->assertSame('mall-only', TemplateProductService::active()['key']);
    }

    public function test_data_subscription_product_enables_data_and_digital_services_only(): void
    {
        $this->actingAs($this->developer())
            ->post('/developer/products/data-subscription/apply')
            ->assertRedirect();

        $this->assertTrue(FeatureSettings::enabled('data'));
        $this->assertTrue(FeatureSettings::enabled('subscriptions'));
        $this->assertFalse(FeatureSettings::enabled('shop'));
        $this->assertTrue(PatupaaSettings::enabled('data_bundle'));
        $this->assertTrue(PatupaaSettings::enabled('digital'));
        $this->assertFalse(PatupaaSettings::enabled('waec'));
        $this->assertSame('emerald-brass', ThemeSettings::currentKey());
    }

    public function test_admins_cannot_apply_template_products(): void
    {
        $admin = User::create([
            'full_name' => 'Product Admin',
            'email' => 'product-admin@example.com',
            'phone' => '05'.random_int(100000000, 999999999),
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->post('/developer/products/mall-only/apply')
            ->assertForbidden();
    }
}
