<?php

namespace Tests\Feature;

use App\Models\PatupaaCatalogItem;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Services\Patupaa\PatupaaSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PatupaaServicesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private bool $failOrders = false;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.patupaa.base_url' => 'https://patupaa.test/api/v1',
            'services.patupaa.api_key' => 'pk_test',
            'services.patupaa.api_secret' => 'sk_test',
        ]);

        $this->user = User::create([
            'full_name' => 'Service Buyer',
            'email' => 'service-buyer@example.com',
            'phone' => '0244000080',
            'balance' => 1000,
            'password' => bcrypt('password'),
        ]);

        PatupaaSettings::setToggles([
            'data_bundle' => true,
            'digital' => true,
            'waec' => true,
            'utility' => true,
            'afa' => true,
        ]);
        PatupaaSettings::setAfaPrice(100);

        $this->failOrders = false;
        $this->registerOrderFake();
    }

    private function fakeOrders(bool $fail = false): void
    {
        $this->failOrders = $fail;
    }

    private function registerOrderFake(): void
    {
        Http::fake(function ($request) {
            $fail = $this->failOrders;
            $url = $request->url();

            if (str_contains($url, '/utilities/lookup')) {
                return Http::response(['success' => true, 'data' => [
                    'account' => 'METER-1',
                    'customer_name' => 'Ama Mensah',
                    'amount_due' => '100.00',
                ]]);
            }

            if (str_contains($url, '/afa')) {
                return Http::response(['success' => true, 'data' => [
                    'registration_reference' => 'AFA-TEST-1',
                    'status' => 'processing',
                    'amount' => '100.00',
                ]], 201);
            }

            if (str_contains($url, '/orders')) {
                if ($fail) {
                    return Http::response([
                        'success' => false,
                        'error' => ['code' => 'PROVIDER_ERROR', 'message' => 'Provider rejected the order.'],
                    ], 422);
                }

                return Http::response(['success' => true, 'data' => [
                    'reference' => 'API-ORDER-1',
                    'order_status' => 'processing',
                    'payment_status' => 'paid',
                ]], 201);
            }

            return Http::response(['success' => true, 'data' => []]);
        });
    }

    private function dataItem(array $attributes = []): PatupaaCatalogItem
    {
        return PatupaaCatalogItem::create(array_merge([
            'remote_type' => 'data_bundle',
            'remote_id' => '1',
            'plan_code' => 'MTN-1GB',
            'name' => 'MTN 1GB',
            'network' => 'MTN',
            'data_value' => '1GB',
            'price' => 5,
            'remote_enabled' => true,
            'admin_enabled' => true,
        ], $attributes));
    }

    public function test_services_pages_are_browsable_before_login(): void
    {
        $this->dataItem();

        $this->get('/services')->assertOk()->assertSee('Data and digital services');
        $this->get('/services/data')->assertOk()->assertSee('MTN 1GB');
    }

    public function test_services_hub_renders_available_services(): void
    {
        $this->dataItem();

        $this->actingAs($this->user)
            ->get('/services')
            ->assertOk()
            ->assertSee('Data and digital services')
            ->assertSee('Data bundles')
            ->assertSee('AFA registration');
    }

    public function test_data_purchase_debits_wallet_and_calls_patupaa(): void
    {
        $item = $this->dataItem();

        $response = $this->actingAs($this->user)
            ->post('/services/data/'.$item->id, ['beneficiary' => '0244000000']);

        $order = ServiceOrder::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('services.orders.show', $order->reference));

        $this->assertSame('data_bundle', $order->remote_type);
        $this->assertSame('API-ORDER-1', $order->remote_reference);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame(995.0, (float) $this->user->fresh()->balance);

        $payload = Http::recorded()->first(fn ($pair) => str_ends_with($pair[0]->url(), '/orders'))[0]->data();
        $this->assertSame('data_bundle', $payload['type']);
        $this->assertSame('MTN-1GB', $payload['plan_code']);
        $this->assertSame('0244000000', $payload['beneficiary']);
    }

    public function test_unavailable_items_cannot_be_bought(): void
    {
        $item = $this->dataItem(['admin_enabled' => false]);

        $this->actingAs($this->user)
            ->post('/services/data/'.$item->id, ['beneficiary' => '0244000000'])
            ->assertNotFound();

        $item->update(['admin_enabled' => true, 'remote_enabled' => false]);

        $this->actingAs($this->user)
            ->post('/services/data/'.$item->id, ['beneficiary' => '0244000000'])
            ->assertNotFound();

        $item->update(['remote_enabled' => true]);
        PatupaaSettings::setToggles(['data_bundle' => false]);

        $this->actingAs($this->user)->get('/services/data')->assertNotFound();

        $this->assertSame(0, ServiceOrder::query()->count());
        $this->assertSame(1000.0, (float) $this->user->fresh()->balance);
    }

    public function test_insufficient_wallet_blocks_purchase_without_api_call(): void
    {
        $this->user->update(['balance' => 1]);
        $item = $this->dataItem();

        $this->actingAs($this->user)
            ->post('/services/data/'.$item->id, ['beneficiary' => '0244000000'])
            ->assertSessionHasErrors('wallet');

        $this->assertSame(0, ServiceOrder::query()->count());
        $this->assertSame(1.0, (float) $this->user->fresh()->balance);
    }

    public function test_patupaa_failure_refunds_the_wallet(): void
    {
        $this->fakeOrders(true);
        $item = $this->dataItem();

        $this->actingAs($this->user)
            ->post('/services/data/'.$item->id, ['beneficiary' => '0244000000'])
            ->assertRedirect();

        $order = ServiceOrder::query()->latest('id')->firstOrFail();

        $this->assertSame('failed', $order->status);
        $this->assertSame('refunded', $order->payment_status);
        $this->assertFalse($order->wallet_debited);
        $this->assertSame(1000.0, (float) $this->user->fresh()->balance);
    }

    public function test_waec_utility_and_subscription_orders_use_correct_payloads(): void
    {
        $waec = PatupaaCatalogItem::create([
            'remote_type' => 'waec', 'remote_id' => '30', 'name' => 'WAEC Checker', 'price' => 20,
            'remote_enabled' => true, 'admin_enabled' => true,
        ]);
        $utility = PatupaaCatalogItem::create([
            'remote_type' => 'utility', 'remote_id' => 'ecg', 'name' => 'ECG Prepaid', 'price' => 0,
            'meta' => ['fee' => '2.00', 'min_amount' => '5.00', 'max_amount' => '1000.00'],
            'remote_enabled' => true, 'admin_enabled' => true,
        ]);
        $digital = PatupaaCatalogItem::create([
            'remote_type' => 'digital', 'remote_id' => '10', 'name' => 'Netflix Gift Card', 'price' => 50,
            'meta' => ['gift_card' => ['region_code' => 'US', 'region_name' => 'United States']],
            'remote_enabled' => true, 'admin_enabled' => true,
        ]);

        $this->actingAs($this->user)->post('/services/waec/'.$waec->id, ['quantity' => 2])->assertRedirect();
        $waecOrder = ServiceOrder::query()->latest('id')->firstOrFail();
        $this->assertSame(40.0, (float) $waecOrder->amount);
        $this->assertSame(960.0, (float) $this->user->fresh()->balance);

        $this->actingAs($this->user)->post('/services/utility/'.$utility->id, [
            'beneficiary' => 'METER-1',
            'amount' => 100,
            'customer_name' => 'Ama Mensah',
        ])->assertRedirect();
        $utilityOrder = ServiceOrder::query()->latest('id')->firstOrFail();
        $this->assertSame(102.0, (float) $utilityOrder->amount);
        $this->assertSame(858.0, (float) $this->user->fresh()->balance);

        $this->actingAs($this->user)->post('/services/subscriptions/'.$digital->id, [
            'recipient_email' => 'buyer@example.com',
            'region_confirmed' => 1,
            'terms_accepted' => 1,
        ])->assertRedirect();

        $digitalOrder = ServiceOrder::query()->latest('id')->firstOrFail();
        $this->assertSame('digital', $digitalOrder->remote_type);
        $this->assertSame('buyer@example.com', $digitalOrder->beneficiary);
        $this->assertSame(808.0, (float) $this->user->fresh()->balance);

        $requests = Http::recorded()->map(fn ($pair) => $pair[0])->filter(fn ($request) => str_ends_with($request->url(), '/orders'));
        $payloads = $requests->map(fn ($request) => $request->data())->values();

        $this->assertTrue($payloads->contains(fn ($payload) => $payload['type'] === 'waec' && $payload['quantity'] === 2));
        $this->assertTrue($payloads->contains(fn ($payload) => $payload['type'] === 'utility' && $payload['service_key'] === 'ecg' && $payload['amount'] === '100.00'));
        $this->assertTrue($payloads->contains(fn ($payload) => $payload['type'] === 'digital' && $payload['account_region'] === 'US' && $payload['region_confirmed'] === true));
    }

    public function test_afa_registration_uses_patupaa_afa_endpoint_and_local_price(): void
    {
        $this->actingAs($this->user)->post('/services/afa', [
            'full_name' => 'Ama Mensah',
            'phone' => '0244000000',
            'ghana_card' => 'GHA-123456789-0',
            'town' => 'Accra',
        ])->assertRedirect();

        $order = ServiceOrder::query()->latest('id')->firstOrFail();

        $this->assertSame('afa', $order->remote_type);
        $this->assertSame(100.0, (float) $order->amount);
        $this->assertSame('AFA-TEST-1', $order->remote_reference);
        $this->assertSame(900.0, (float) $this->user->fresh()->balance);

        $request = Http::recorded()->first(fn ($pair) => str_ends_with($pair[0]->url(), '/afa'))[0];
        $this->assertSame('GHA-123456789-0', $request->data()['ghana_card']);
    }

    public function test_service_orders_pages_render_for_the_owner(): void
    {
        $item = $this->dataItem();

        $this->actingAs($this->user)->post('/services/data/'.$item->id, ['beneficiary' => '0244000000']);

        $order = ServiceOrder::query()->latest('id')->firstOrFail();

        $this->actingAs($this->user)
            ->get('/services/orders')
            ->assertOk()
            ->assertSee($order->reference);

        $this->actingAs($this->user)
            ->get('/services/orders/'.$order->reference)
            ->assertOk()
            ->assertSee('API-ORDER-1');
    }
}
