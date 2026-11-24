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
    public function test_product_detail_uses_a_provider_style_catalog_structure(): void
    {
        $this->actingAs($this->developer())
            ->get('/developer/products/mall-only')
            ->assertOk()
            ->assertSee('License pricing')
            ->assertSee('Standard license')
            ->assertSee('Extended license')
            ->assertSee('GHS 2,500.00')
            ->assertSee('180 days support')
            ->assertSee('Feature matrix')
            ->assertSee("What's included", false)
            ->assertSee('Changelog')
            ->assertSee('Live preview')
            ->assertSee('E-commerce');
    }

    public function test_license_issued_from_a_product_tier_stores_price_and_support_terms(): void
    {
        $this->actingAs($this->developer())
            ->post('/developer/licenses', [
                'client_name' => 'Mall Client',
                'client_email' => 'client@example.com',
                'domain' => 'mall.example.com',
                'agreement_reference' => 'AGR-MALL-1',
                'product_key' => 'mall-only',
                'license_tier' => 'standard',
            ])
            ->assertRedirect(route('developer.licenses'));

        $license = \App\Models\TemplateLicense::query()->firstOrFail();

        $this->assertSame('mall-only', $license->product_key);
        $this->assertSame('standard', $license->license_tier);
        $this->assertSame('2500.00', (string) $license->price);
        $this->assertSame(1, $license->deployments_allowed);
        $this->assertNotNull($license->support_ends_at);
        $this->assertTrue($license->support_ends_at->isFuture());
    }

    public function test_product_license_requires_a_valid_tier(): void
    {
        $this->actingAs($this->developer())
            ->post('/developer/licenses', [
                'client_name' => 'Mall Client',
                'product_key' => 'mall-only',
                'license_tier' => 'enterprise',
            ])
            ->assertSessionHasErrors('license_tier');

        $this->assertSame(0, \App\Models\TemplateLicense::query()->count());
    }
}