<?php

namespace Tests\Feature;

use App\Models\PatupaaCatalogItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminServicesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'full_name' => 'Services Admin',
            'email' => 'services-admin@example.com',
            'phone' => '0244000070',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    private function developer(): User
    {
        return User::create([
            'full_name' => 'Services Developer',
            'email' => 'services-developer@example.com',
            'phone' => '0244000071',
            'password' => bcrypt('password'),
            'role' => User::ROLE_DEVELOPER,
        ]);
    }

    private function token(User $admin): string
    {
        return $this->postJson('/api/auth/login', [
            'login' => $admin->email,
            'password' => 'password',
        ])->assertOk()->json('access_token');
    }

    public function test_services_catalog_page_renders_in_admin_shell(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/services')
            ->assertOk()
            ->assertSee('Services catalog')
            ->assertSee('Sync from provider')
            ->assertSee('Admin navigation')
            ->assertDontSee('Provider sources');
    }

    public function test_developer_sees_provider_sources_on_developer_page(): void
    {
        $this->actingAs($this->developer())
            ->get('/developer/services')
            ->assertOk()
            ->assertSee('Provider sources')
            ->assertSee('Service availability');
    }

    public function test_admin_can_toggle_items_and_service_switches(): void
    {
        config([
            'services.patupaa.api_key' => 'pk_test',
            'services.patupaa.api_secret' => 'sk_test',
            'services.patupaa.base_url' => 'https://patupaa.test/api/v1',
        ]);

        $item = PatupaaCatalogItem::create([
            'remote_type' => 'data_bundle',
            'remote_id' => '1',
            'name' => 'MTN 1GB',
            'price' => 5.20,
            'remote_enabled' => true,
            'admin_enabled' => true,
        ]);

        $headers = ['Authorization' => 'Bearer '.$this->token($this->admin())];

        $this->withHeaders($headers)
            ->getJson('/api/admin/services')
            ->assertOk()
            ->assertJsonPath('items.total', 1)
            ->assertJsonPath('configured', true);

        $this->withHeaders($headers)
            ->putJson('/api/admin/services/catalog/'.$item->id, ['admin_enabled' => false])
            ->assertOk()
            ->assertJsonPath('item.admin_enabled', false);

        $this->assertFalse($item->fresh()->admin_enabled);

        $this->withHeaders($headers)
            ->putJson('/api/admin/services/toggles', ['toggles' => ['waec' => false]])
            ->assertForbidden();

        $this->withHeaders($headers)
            ->getJson('/api/admin/services')
            ->assertOk()
            ->assertJsonCount(0, 'providers');

        $provider = \App\Models\ServiceProvider::create([
            'name' => 'Dev Source',
            'driver' => 'patupaa',
            'base_url' => 'https://dev-source.test/api/v1',
            'api_key' => 'dev-key',
            'api_secret' => 'dev-secret',
            'services' => ['data_bundle'],
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->withHeaders($headers)
            ->putJson('/api/admin/services/providers/'.$provider->id, ['name' => 'Hijack'])->assertForbidden();

        $devHeaders = ['Authorization' => 'Bearer '.$this->token($this->developer())];

        $this->withHeaders($devHeaders)
            ->putJson('/api/admin/services/toggles', ['toggles' => ['waec' => false, 'utility' => true]])
            ->assertOk()
            ->assertJsonPath('toggles.waec', false);

        $this->withHeaders($devHeaders)
            ->getJson('/api/admin/services')
            ->assertOk()
            ->assertJsonPath('providers.0.name', 'Dev Source');
    }

    public function test_admin_sync_endpoint_returns_summary(): void
    {
        config([
            'services.patupaa.api_key' => 'pk_test',
            'services.patupaa.api_secret' => 'sk_test',
            'services.patupaa.base_url' => 'https://patupaa.test/api/v1',
        ]);

        Http::fake(function ($request) {
            $payload = str_contains($request->url(), '/utility-services')
                ? [['key' => 'ecg', 'name' => 'ECG Prepaid', 'fee' => '2.00']]
                : [];

            return Http::response(['success' => true, 'data' => $payload]);
        });

        $headers = ['Authorization' => 'Bearer '.$this->token($this->admin())];

        $this->withHeaders($headers)
            ->postJson('/api/admin/services/sync')
            ->assertOk()
            ->assertJsonPath('summary.utility.synced', 1);

        $this->assertSame(1, PatupaaCatalogItem::query()->where('remote_type', 'utility')->count());
    }

    public function test_services_admin_api_requires_authentication(): void
    {
        $this->getJson('/api/admin/services')->assertUnauthorized();
    }
    public function test_service_source_settings_are_only_in_the_developer_console(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/developer/services')->assertForbidden();

        $this->actingAs($this->developer())
            ->get('/admin/services')
            ->assertOk()
            ->assertDontSee('Provider sources')
            ->assertDontSee('Service availability');
    }
}