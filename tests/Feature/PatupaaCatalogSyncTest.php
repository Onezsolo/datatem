<?php

namespace Tests\Feature;

use App\Models\PatupaaCatalogItem;
use App\Services\Patupaa\PatupaaCatalogSync;
use App\Services\Patupaa\PatupaaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PatupaaCatalogSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.patupaa.base_url' => 'https://patupaa.test/api/v1',
            'services.patupaa.api_key' => 'pk_test',
            'services.patupaa.api_secret' => 'sk_test',
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $dataBundles
     * @param  array<int, array<string, mixed>>  $digital
     * @param  array<int, array<string, mixed>>  $airtime
     * @param  array<int, array<string, mixed>>  $waec
     * @param  array<int, array<string, mixed>>  $utility
     */
    private function fakeApi(array $dataBundles, array $digital = [], array $airtime = [], array $waec = [], array $utility = []): void
    {
        Http::fake(function ($request) use ($dataBundles, $digital, $airtime, $waec, $utility) {
            $url = $request->url();

            $payload = match (true) {
                str_contains($url, '/data-bundles') => $dataBundles,
                str_contains($url, '/products') && str_contains($url, 'type=digital') => $digital,
                str_contains($url, '/products') && str_contains($url, 'type=airtime') => $airtime,
                str_contains($url, '/waec-products') => $waec,
                str_contains($url, '/utility-services') => $utility,
                default => [],
            };

            return Http::response(['success' => true, 'message' => 'OK', 'data' => $payload]);
        });
    }

    public function test_sync_mirrors_patupaa_catalog_and_availability(): void
    {
        $this->fakeApi(
            [
                ['id' => 1, 'name' => 'MTN 1GB', 'plan_code' => 'MTN-1GB', 'network' => 'MTN', 'data_value' => '1GB', 'validity' => '30 days', 'price' => '6.00', 'api_price' => '5.20', 'api_price_source' => 'custom'],
            ],
            [
                ['id' => 10, 'type' => 'digital', 'name' => 'Netflix Gift Card', 'price' => '100.00', 'api_price' => '95.00', 'gift_card' => ['region_code' => 'US']],
            ],
            [
                ['id' => 20, 'type' => 'airtime', 'name' => 'MTN Airtime', 'price' => '1.00', 'api_price' => '1.00', 'min_amount' => '1.00', 'max_amount' => '500.00'],
            ],
            [
                ['id' => 30, 'name' => 'WAEC Results Checker', 'price' => '25.00', 'api_price' => '24.00', 'available' => 0],
            ],
            [
                ['key' => 'ecg', 'name' => 'ECG Prepaid', 'description' => 'Electricity', 'customer_label' => 'Meter number', 'fee' => '2.00', 'min_amount' => '5.00', 'max_amount' => '1000.00'],
            ]
        );

        $summary = app(PatupaaCatalogSync::class)->sync();

        $this->assertSame(1, $summary['data_bundle']['synced']);
        $this->assertSame(1, $summary['digital']['synced']);
        $this->assertSame(1, $summary['waec']['synced']);
        $this->assertSame(1, $summary['utility']['synced']);

        $bundle = PatupaaCatalogItem::query()->where('remote_type', 'data_bundle')->firstOrFail();
        $this->assertSame('MTN-1GB', $bundle->plan_code);
        $this->assertSame('5.20', (string) $bundle->price);
        $this->assertTrue($bundle->remote_enabled);

        $waec = PatupaaCatalogItem::query()->where('remote_type', 'waec')->firstOrFail();
        $this->assertFalse($waec->remote_enabled);

        $utility = PatupaaCatalogItem::query()->where('remote_type', 'utility')->firstOrFail();
        $this->assertSame('2.00', (string) $utility->price);
        $this->assertSame('Meter number', $utility->meta['customer_label']);
    }

    public function test_items_missing_from_patupaa_are_disabled_on_next_sync(): void
    {
        $bundles = [
            ['id' => 1, 'name' => 'MTN 1GB', 'plan_code' => 'MTN-1GB', 'price' => '6.00', 'api_price' => '5.20'],
            ['id' => 2, 'name' => 'AT 5GB', 'plan_code' => 'AT-5GB', 'price' => '12.00', 'api_price' => '11.00'],
        ];

        Http::fake(function ($request) use (&$bundles) {
            $payload = str_contains($request->url(), '/data-bundles') ? $bundles : [];

            return Http::response(['success' => true, 'data' => $payload]);
        });

        app(PatupaaCatalogSync::class)->sync();
        $this->assertSame(2, PatupaaCatalogItem::query()->where('remote_enabled', true)->count());

        $bundles = [
            ['id' => 1, 'name' => 'MTN 1GB', 'plan_code' => 'MTN-1GB', 'price' => '6.00', 'api_price' => '5.20'],
        ];

        $summary = app(PatupaaCatalogSync::class)->sync();

        $this->assertSame(1, $summary['data_bundle']['disabled']);
        $this->assertFalse(PatupaaCatalogItem::query()->where('remote_id', '2')->firstOrFail()->remote_enabled);
        $this->assertTrue(PatupaaCatalogItem::query()->where('remote_id', '1')->firstOrFail()->remote_enabled);
    }

    public function test_admin_override_and_global_toggles_gate_availability(): void
    {
        $item = PatupaaCatalogItem::create([
            'remote_type' => 'data_bundle',
            'remote_id' => '1',
            'name' => 'MTN 1GB',
            'price' => 5.20,
            'remote_enabled' => true,
            'admin_enabled' => false,
        ]);

        $this->assertFalse($item->isAvailable());
        $this->assertSame(0, PatupaaCatalogItem::query()->available()->count());

        $item->update(['admin_enabled' => true]);
        $this->assertSame(1, PatupaaCatalogItem::query()->available()->count());

        $toggles = PatupaaSettings::setToggles(['waec' => false]);
        $this->assertFalse($toggles['waec']);
        $this->assertFalse(PatupaaSettings::enabled('waec'));
        $this->assertTrue(PatupaaSettings::enabled('data_bundle'));
    }
}
