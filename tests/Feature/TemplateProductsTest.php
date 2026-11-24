<?php

namespace Tests\Feature;

use App\Models\TemplatePackage;
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
            ->assertSee('Data only')
            ->assertSee('Open template')
            ->assertSee('Preview');
    }

    public function test_each_product_has_its_own_console_path_and_preview_without_touching_the_deployment(): void
    {
        ThemeSettings::set('navy-gold');
        FeatureSettings::set(['shop' => true, 'data' => true]);
        PatupaaSettings::setToggles(['data_bundle' => true, 'digital' => true]);
        PatupaaSettings::setAfaPrice(120);

        $this->actingAs($this->developer());

        $this->get('/developer/products/mall-only')
            ->assertOk()
            ->assertSee('Super Mall shopping with wallet checkout only.')
            ->assertSee('Ocean & Steel')
            ->assertSee('/developer/products/mall-only/preview');

        $this->get('/developer/products/mall-only/preview')
            ->assertOk()
            ->assertSee('not applied to this deployment')
            ->assertSee('Mall only template');

        $this->get('/developer/products/data-subscription')
            ->assertOk()
            ->assertSee('Emerald & Brass');

        $this->assertSame('navy-gold', ThemeSettings::currentKey());
        $this->assertTrue(FeatureSettings::enabled('shop'));
        $this->assertTrue(FeatureSettings::enabled('data'));
        $this->assertTrue(PatupaaSettings::enabled('data_bundle'));
        $this->assertSame(120.0, PatupaaSettings::afaPrice());
    }

    public function test_creating_a_client_package_from_a_product_does_not_change_the_deployment(): void
    {
        ThemeSettings::set('navy-gold');
        FeatureSettings::set(['shop' => true, 'data' => true]);
        PatupaaSettings::setToggles(['data_bundle' => true, 'digital' => true]);

        $this->actingAs($this->developer());

        $this->post('/developer/products/mall-only/package')
            ->assertRedirect(route('developer.products.show', 'mall-only'));

        $package = TemplatePackage::query()->firstOrFail();

        $this->assertSame('Mall only', $package->name);
        $this->assertSame('ocean-steel', $package->payload['theme']);
        $this->assertFalse($package->payload['features']['data']);
        $this->assertTrue($package->payload['features']['shop']);
        $this->assertFalse($package->payload['services']['data_bundle']);

        $this->assertSame('navy-gold', ThemeSettings::currentKey());
        $this->assertTrue(FeatureSettings::enabled('data'));
        $this->assertTrue(FeatureSettings::enabled('shop'));
        $this->assertTrue(PatupaaSettings::enabled('data_bundle'));
    }

    public function test_admins_cannot_use_template_product_paths(): void
    {
        $admin = User::create([
            'full_name' => 'Product Admin',
            'email' => 'product-admin@example.com',
            'phone' => '05'.random_int(100000000, 999999999),
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)->get('/developer/products/mall-only')->assertForbidden();
        $this->actingAs($admin)->get('/developer/products/mall-only/preview')->assertForbidden();
        $this->actingAs($admin)->post('/developer/products/mall-only/package')->assertForbidden();
    }
}
