<?php

namespace Tests\Feature;

use App\Models\PatupaaCatalogItem;
use App\Models\ServiceOrder;
use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProviderSourceTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        $admin = User::create([
            'full_name' => 'Provider Admin',
            'email' => 'provider-admin@example.com',
            'phone' => '0244000200',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        return $this->postJson('/api/auth/login', [
            'login' => $admin->email,
            'password' => 'password',
        ])->assertOk()->json('access_token');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function provider(array $overrides = []): ServiceProvider
    {
        return ServiceProvider::create(array_merge([
            'name' => 'Source B',
            'driver' => 'patupaa',
            'base_url' => 'https://source-b.test/api/v1',
            'api_key' => 'source-b-key',
            'api_secret' => 'source-b-secret',
            'services' => ['data_bundle'],
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 1,
        ], $overrides));
    }

    public function test_admin_can_add_edit_default_and_delete_sources_without_exposing_secrets(): void
    {
        $token = $this->adminToken();
        $headers = ['Authorization' => 'Bearer '.$token];

        $created = $this->withHeaders($headers)
            ->postJson('/api/admin/services/providers', [
                'name' => 'Source B',
                'base_url' => 'https://source-b.test/api/v1',
                'api_key' => 'source-b-key',
                'api_secret' => 'source-b-secret',
                'services' => ['data_bundle', 'waec'],
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ])
            ->assertCreated()
            ->json('provider');

        $this->assertTrue($created['is_default']);
        $this->assertArrayNotHasKey('api_key', $created);
        $this->assertArrayNotHasKey('api_secret', $created);

        $provider = ServiceProvider::findOrFail($created['id']);
        $this->assertSame('source-b-key', $provider->api_key);

        $this->withHeaders($headers)
            ->putJson('/api/admin/services/providers/'.$provider->id, [
                'name' => 'Source B renamed',
                'base_url' => 'https://source-b.test/api/v1',
                'services' => ['data_bundle'],
                'is_active' => true,
                'sort_order' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('provider.name', 'Source B renamed');

        $this->assertSame('source-b-secret', $provider->fresh()->api_secret);

        $second = $this->provider(['name' => 'Source C', 'base_url' => 'https://source-c.test/api/v1']);

        $this->withHeaders($headers)
            ->postJson('/api/admin/services/providers/'.$second->id.'/default')
            ->assertOk()
            ->assertJsonPath('provider.is_default', true);

        $this->withHeaders($headers)
            ->deleteJson('/api/admin/services/providers/'.$provider->id)
            ->assertOk();

        $this->assertNull(ServiceProvider::find($provider->id));
    }

    public function test_sync_uses_the_selected_source_credentials_and_records_provider(): void
    {
        $provider = $this->provider();

        Http::fake(function (Request $request) {
            $this->assertStringContainsString('source-b.test', $request->url());
            $this->assertSame('source-b-key', $request->header('X-API-Key')[0] ?? null);

            $payload = str_contains($request->url(), '/data-bundles')
                ? [['id' => 7, 'name' => 'MTN 2GB', 'plan_code' => 'MTN-2GB', 'price' => '10.00', 'api_price' => '9.00']]
                : [];

            return Http::response(['success' => true, 'data' => $payload]);
        });

        $headers = ['Authorization' => 'Bearer '.$this->adminToken()];

        $this->withHeaders($headers)
            ->postJson('/api/admin/services/providers/'.$provider->id.'/sync')
            ->assertOk()
            ->assertJsonPath('summary.data_bundle.synced', 1);

        $item = PatupaaCatalogItem::query()->where('provider_id', $provider->id)->firstOrFail();

        $this->assertSame('MTN-2GB', $item->plan_code);
        $this->assertSame('9.00', (string) $item->price);
        $this->assertNotNull($provider->fresh()->last_synced_at);
    }

    public function test_purchase_uses_the_items_own_source(): void
    {
        $provider = $this->provider();
        $user = User::create([
            'full_name' => 'Source Buyer',
            'email' => 'source-buyer@example.com',
            'phone' => '0244000201',
            'balance' => 100,
            'password' => bcrypt('password'),
        ]);

        $item = PatupaaCatalogItem::create([
            'provider_id' => $provider->id,
            'remote_type' => 'data_bundle',
            'remote_id' => '7',
            'plan_code' => 'MTN-2GB',
            'name' => 'MTN 2GB',
            'price' => 9,
            'remote_enabled' => true,
            'admin_enabled' => true,
        ]);

        Http::fake(function (Request $request) {
            $this->assertStringContainsString('source-b.test', $request->url());
            $this->assertSame('source-b-key', $request->header('X-API-Key')[0] ?? null);

            return Http::response(['success' => true, 'data' => [
                'reference' => 'SOURCE-B-ORDER-1',
                'order_status' => 'processing',
            ]], 201);
        });

        $this->actingAs($user)
            ->post('/services/data/'.$item->id, ['beneficiary' => '0244000201'])
            ->assertRedirect();

        $order = ServiceOrder::query()->latest('id')->firstOrFail();

        $this->assertSame($provider->id, $order->provider_id);
        $this->assertSame('SOURCE-B-ORDER-1', $order->remote_reference);
        $this->assertSame(91.0, (float) $user->fresh()->balance);
    }

    public function test_inactive_source_items_cannot_be_browsed_or_bought(): void
    {
        $provider = $this->provider(['is_active' => false]);

        $item = PatupaaCatalogItem::create([
            'provider_id' => $provider->id,
            'remote_type' => 'data_bundle',
            'remote_id' => '7',
            'plan_code' => 'MTN-2GB',
            'name' => 'MTN 2GB',
            'price' => 9,
            'remote_enabled' => true,
            'admin_enabled' => true,
        ]);

        $this->get('/services/data')->assertOk()->assertDontSee('MTN 2GB');

        $user = User::create([
            'full_name' => 'Source Buyer',
            'email' => 'source-buyer-2@example.com',
            'phone' => '0244000202',
            'balance' => 100,
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user)
            ->post('/services/data/'.$item->id, ['beneficiary' => '0244000202'])
            ->assertNotFound();

        $this->assertSame(0, ServiceOrder::query()->count());
    }
}
