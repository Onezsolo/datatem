<?php

namespace Tests\Feature;

use App\Models\TemplateLicense;
use App\Models\User;
use App\Services\TemplateLicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeveloperConsoleTest extends TestCase
{
    use RefreshDatabase;

    private function developer(): User
    {
        return User::create([
            'full_name' => 'License Dev',
            'email' => 'license-dev-'.uniqid().'@example.com',
            'phone' => '02'.random_int(100000000, 999999999),
            'password' => bcrypt('password'),
            'role' => User::ROLE_DEVELOPER,
        ]);
    }

    public function test_command_creates_a_developer_account(): void
    {
        $this->artisan('template:dev-account', [
            'email' => 'dev-command@example.com',
            '--password' => 'secret123',
            '--name' => 'Command Dev',
        ])->assertExitCode(0);

        $user = User::query()->where('email', 'dev-command@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_DEVELOPER, $user->role);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_developer_console_is_restricted_to_developers(): void
    {
        $this->get('/developer')->assertRedirectContains('/login');

        $admin = User::create([
            'full_name' => 'Regular Admin',
            'email' => 'regular-admin@example.com',
            'phone' => '0244000401',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)->get('/developer')->assertForbidden();

        $this->actingAs($this->developer())
            ->get('/developer')
            ->assertOk()
            ->assertSee('Developer console');

        $this->actingAs($this->developer())
            ->get('/developer/licenses')
            ->assertOk()
            ->assertSee('Issue license');
    }

    public function test_developer_can_issue_revoke_and_reinstate_licenses(): void
    {
        $developer = $this->developer();
        $this->actingAs($developer);

        $response = $this->post('/developer/licenses', [
            'client_name' => 'Acme Stores',
            'client_email' => 'ops@acme.example',
            'domain' => 'acme.example',
            'agreement_reference' => 'AGR-2026-01',
            'expires_at' => now()->addYear()->toDateString(),
            'notes' => 'Template issue under agreement.',
        ]);

        $response->assertRedirect(route('developer.licenses'));
        $response->assertSessionHas('issued_license_key');

        $key = (string) session('issued_license_key');
        $this->assertMatchesRegularExpression('/^DTMP(?:-[A-Z0-9]{4}){4}$/', $key);

        $license = TemplateLicense::query()->firstOrFail();
        $this->assertNotSame($key, $license->license_key_hash);
        $this->assertSame(TemplateLicense::hashKey($key), $license->license_key_hash);

        $service = app(TemplateLicenseService::class);
        $this->assertNotNull($service->verify($key));

        $this->actingAs($developer)
            ->post('/developer/licenses/'.$license->id.'/revoke')
            ->assertRedirect();

        $this->assertNull($service->verify($key));

        $this->actingAs($developer)
            ->post('/developer/licenses/'.$license->id.'/reinstate')
            ->assertRedirect();

        $this->assertNotNull(app(TemplateLicenseService::class)->verify($key));
    }

    public function test_expired_license_is_not_valid(): void
    {
        $key = TemplateLicense::generatePlainKey();

        TemplateLicense::create([
            'license_key_hash' => TemplateLicense::hashKey($key),
            'key_prefix' => TemplateLicense::prefixForKey($key),
            'client_name' => 'Expired Client',
            'issued_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
            'status' => 'active',
        ]);

        $this->assertNull(app(TemplateLicenseService::class)->verify($key));
    }

    public function test_deployment_license_can_be_set_and_verified(): void
    {
        $developer = $this->developer();
        $this->actingAs($developer);

        $issued = app(TemplateLicenseService::class)->issue([
            'client_name' => 'Deployment Client',
            'domain' => 'client.example',
        ], $developer);

        $this->post('/developer/deployment-license', ['license_key' => $issued['key']])
            ->assertRedirect()
            ->assertSessionHas('status');

        $status = app(TemplateLicenseService::class)->deploymentStatus();

        $this->assertSame('active', $status['state']);
        $this->assertSame($issued['license']->key_prefix, $status['prefix']);

        $this->post('/developer/deployment-license', ['license_key' => 'DTMP-INVALID'])
            ->assertSessionHasErrors('license_key');
    }

    public function test_developer_is_blocked_from_the_business_admin_console(): void
    {
        $this->actingAs($this->developer())
            ->get('/admin/dashboard')
            ->assertForbidden();
    }
}
