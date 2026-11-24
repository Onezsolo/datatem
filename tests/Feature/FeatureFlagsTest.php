<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FeatureSettings;
use App\Services\ThemeSettings;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagsTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $admin = User::create([
            'full_name' => 'Feature Admin',
            'email' => 'feature-admin@example.com',
            'phone' => '0244000300',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        return $this->postJson('/api/auth/login', [
            'login' => $admin->email,
            'password' => 'password',
        ])->assertOk()->json('access_token');
    }

    public function test_admin_can_disable_and_re_enable_a_feature(): void
    {
        $headers = ['Authorization' => 'Bearer '.$this->adminToken()];

        $this->assertTrue(FeatureSettings::enabled('shop'));

        $this->withHeaders($headers)
            ->putJson('/api/admin/settings/features', ['features' => ['shop' => false]])
            ->assertOk()
            ->assertJsonPath('features.shop', false);

        $this->assertFalse(FeatureSettings::enabled('shop'));
        $this->assertNotContains('Super Mall', array_column(Navigation::public(), 'label'));
        $this->get('/shop')->assertNotFound();
        $this->get('/admin/services')->assertOk();

        $this->withHeaders($headers)
            ->putJson('/api/admin/settings/features', ['features' => ['shop' => true]])
            ->assertOk();

        $this->assertTrue(FeatureSettings::enabled('shop'));
        $this->assertContains('Super Mall', array_column(Navigation::public(), 'label'));
    }

    public function test_admin_can_switch_between_five_system_designs(): void
    {
        $headers = ['Authorization' => 'Bearer '.$this->adminToken()];

        $appearance = $this->withHeaders($headers)
            ->getJson('/api/admin/settings/appearance')
            ->assertOk()
            ->json();

        $this->assertCount(5, $appearance['presets']);
        $this->assertContains('navy-gold', collect($appearance['presets'])->pluck('key')->all());

        $this->withHeaders($headers)
            ->putJson('/api/admin/settings/theme', ['theme' => 'ocean-steel'])
            ->assertOk()
            ->assertJsonPath('theme', 'ocean-steel');

        $this->assertSame('ocean-steel', ThemeSettings::currentKey());
        $this->assertSame('#0B2545', ThemeSettings::current()['colors']['brand-navy']);

        $this->get('/')
            ->assertOk()
            ->assertSee('--color-brand-navy: #0B2545', false);
    }

    public function test_disabled_service_is_hidden_and_returns_not_found(): void
    {
        FeatureSettings::set(['waec' => false]);

        $labels = collect(Navigation::member())->flatten(1)->pluck('label');
        $this->assertFalse($labels->contains('WAEC PINs'));
        $this->get('/services/waec')->assertNotFound();
    }
}
