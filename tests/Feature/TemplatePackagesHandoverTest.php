<?php

namespace Tests\Feature;

use App\Models\ServiceProvider;
use App\Models\TemplatePackage;
use App\Models\User;
use App\Services\FeatureSettings;
use App\Services\HandoverService;
use App\Services\Patupaa\PatupaaSettings;
use App\Services\TemplateLicenseService;
use App\Services\ThemeSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplatePackagesHandoverTest extends TestCase
{
    use RefreshDatabase;

    private function developer(): User
    {
        return User::create([
            'full_name' => 'Package Developer',
            'email' => 'package-dev-'.uniqid().'@example.com',
            'phone' => '05'.random_int(100000000, 999999999),
            'password' => bcrypt('password'),
            'role' => User::ROLE_DEVELOPER,
        ]);
    }

    public function test_current_setup_can_be_saved_as_a_package_and_applied(): void
    {
        $developer = $this->developer();
        $this->actingAs($developer);

        ThemeSettings::set('ocean-steel');
        FeatureSettings::set(['shop' => false]);
        PatupaaSettings::setToggles(['waec' => false]);
        PatupaaSettings::setAfaPrice(77);

        $source = ServiceProvider::create([
            'name' => 'Package Source',
            'driver' => 'patupaa',
            'base_url' => 'https://package-source.test/api/v1',
            'api_key' => 'pkg-key',
            'api_secret' => 'pkg-secret',
            'services' => ['data_bundle', 'afa'],
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->post('/developer/packages', [
            'name' => 'Mall Starter',
            'version' => '1.2.0',
            'description' => 'Shop-focused client package.',
        ])->assertRedirect(route('developer.packages'));

        $package = TemplatePackage::query()->firstOrFail();

        $this->assertSame('ocean-steel', $package->payload['theme']);
        $this->assertFalse($package->payload['features']['shop']);
        $this->assertCount(1, $package->payload['providers']);

        // Simulate a different deployment state.
        ThemeSettings::set('navy-gold');
        FeatureSettings::set(['shop' => true]);
        PatupaaSettings::setToggles(['waec' => true]);
        PatupaaSettings::setAfaPrice(0);
        $source->update(['is_active' => false, 'is_default' => false]);

        $this->post('/developer/packages/'.$package->id.'/apply')
            ->assertRedirect(route('developer.dashboard'));

        $this->assertSame('ocean-steel', ThemeSettings::currentKey());
        $this->assertFalse(FeatureSettings::enabled('shop'));
        $this->assertFalse(PatupaaSettings::enabled('waec'));
        $this->assertSame(77.0, PatupaaSettings::afaPrice());

        $restored = ServiceProvider::query()->where('base_url', 'https://package-source.test/api/v1')->firstOrFail();
        $this->assertTrue($restored->is_active);
        $this->assertTrue($restored->is_default);
        $this->assertSame('pkg-key', $restored->api_key);
        $this->assertSame(1, $package->fresh()->applied_count);
    }

    public function test_handover_requires_an_active_license_and_permanently_removes_developer_access(): void
    {
        $developer = $this->developer();
        $this->actingAs($developer);

        $this->post('/developer/handover', ['confirmation' => 'HANDOVER'])
            ->assertSessionHasErrors('confirmation');

        $this->assertFalse(HandoverService::isCompleted());

        $licenses = app(TemplateLicenseService::class);
        $issued = $licenses->issue(['client_name' => 'Handover Client'], $developer);
        $licenses->setDeploymentKey($issued['key']);

        $this->post('/developer/handover', [
            'confirmation' => 'HANDOVER',
            'note' => 'Agreement signed.',
        ])->assertRedirect(route('login'));

        $this->assertTrue(HandoverService::isCompleted());

        $this->actingAs($developer)->get('/developer')->assertForbidden();
        $this->actingAs($developer)->get('/admin/dashboard')->assertForbidden();

        $this->postJson('/api/auth/login', [
            'login' => $developer->email,
            'password' => 'password',
        ])->assertStatus(403);

        $admin = User::create([
            'full_name' => 'After Handover Admin',
            'email' => 'after-handover-admin@example.com',
            'phone' => '05'.random_int(100000000, 999999999),
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();

        $this->postJson('/api/auth/login', [
            'login' => $admin->email,
            'password' => 'password',
        ])->assertOk();
    }
}
