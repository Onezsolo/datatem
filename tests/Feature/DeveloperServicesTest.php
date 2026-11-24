<?php

namespace Tests\Feature;

use App\Models\PatupaaCatalogItem;
use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeveloperServicesTest extends TestCase
{
    use RefreshDatabase;

    private function developer(): User
    {
        return User::create([
            'full_name' => 'Developer Services',
            'email' => 'dev-services-'.uniqid().'@example.com',
            'phone' => '05'.random_int(100000000, 999999999),
            'password' => bcrypt('password'),
            'role' => User::ROLE_DEVELOPER,
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'full_name' => 'Site Admin',
            'email' => 'site-admin-'.uniqid().'@example.com',
            'phone' => '05'.random_int(100000000, 999999999),
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function token(User $user): string
    {
        return $this->postJson('/api/auth/login', [
            'login' => $user->email,
            'password' => 'password',
        ])->assertOk()->json('access_token');
    }

    public function test_service_sources_exist_only_in_the_developer_console(): void
    {
        $this->actingAs($this->developer())
            ->get('/developer/services')
            ->assertOk()
            ->assertSee('Service availability')
            ->assertSee('Provider sources')
            ->assertSee('Catalog items');

        $this->actingAs($this->developer())
            ->get('/admin/services')
            ->assertNotFound();

        $this->actingAs($this->admin())
            ->get('/developer/services')
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get('/admin/services')
            ->assertNotFound();
    }

    public function test_service_management_apis_are_developer_only(): void
    {
        $adminHeaders = ['Authorization' => 'Bearer '.$this->token($this->admin())];

        $this->withHeaders($adminHeaders)->getJson('/api/developer/services')->assertForbidden();
        $this->withHeaders($adminHeaders)->putJson('/api/developer/services/toggles', ['toggles' => ['waec' => false]])->assertForbidden();

        $devHeaders = ['Authorization' => 'Bearer '.$this->token($this->developer())];

        $this->withHeaders($devHeaders)->getJson('/api/developer/services')->assertOk();
    }

    public function test_developer_can_toggle_items_switches_and_sync(): void
    {
        config([
            'services.patupaa.api_key' => 'pk_test',
            'services.patupaa.api_secret' => 'sk_test',
            'services.patupaa.base_url' => 'https://patupaa.test/api/v1',
        ]);

        $provider = ServiceProvider::create([
            'name' => 'Dev Source',
            'driver' => 'patupaa',
            'base_url' => 'https://patupaa.test/api/v1',
            'api_key' => 'pk_test',
            'api_secret' => 'sk_test',
            'services' => ['data_bundle'],
            'is_active' => true,
            'is_default' => true,
        ]);

        $item = PatupaaCatalogItem::create([
            'provider_id' => $provider->id,
            'remote_type' => 'data_bundle',
            'remote_id' => '1',
            'name' => 'MTN 1GB',
            'price' => 5.20,
            'remote_enabled' => true,
            'admin_enabled' => true,
        ]);

        $headers = ['Authorization' => 'Bearer '.$this->token($this->developer())];

        $this->withHeaders($headers)
            ->getJson('/api/developer/services')
            ->assertOk()
            ->assertJsonPath('items.total', 1)
            ->assertJsonPath('providers.0.name', 'Dev Source');

        $this->withHeaders($headers)
            ->putJson('/api/developer/services/catalog/'.$item->id, ['admin_enabled' => false])
            ->assertOk()
            ->assertJsonPath('item.admin_enabled', false);

        $this->withHeaders($headers)
            ->putJson('/api/developer/services/toggles', ['toggles' => ['waec' => false, 'utility' => true]])
            ->assertOk()
            ->assertJsonPath('toggles.waec', false);

        Http::fake(fn () => Http::response(['success' => true, 'data' => [
            ['id' => 1, 'name' => 'MTN 1GB', 'plan_code' => 'MTN-1GB', 'price' => '6.00', 'api_price' => '5.20'],
        ]]));

        $this->withHeaders($headers)
            ->postJson('/api/developer/services/sync')
            ->assertOk()
            ->assertJsonPath('summary.data_bundle.synced', 1);
    }
}
