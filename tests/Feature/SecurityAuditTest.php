<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'full_name' => 'Audit Admin',
            'email' => 'audit-admin-'.uniqid().'@example.com',
            'phone' => '05'.random_int(100000000, 999999999),
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function adminToken(): string
    {
        return $this->postJson('/api/auth/login', [
            'login' => $this->admin()->email,
            'password' => 'password',
        ])->assertOk()->json('access_token');
    }

    public function test_registration_requires_a_minimum_six_character_password(): void
    {
        $base = [
            'full_name' => 'Password Tester',
            'email' => 'password-short-'.uniqid().'@example.com',
            'phone' => '05'.random_int(100000000, 999999999),
        ];

        $short = $this->postJson('/api/auth/register', array_merge($base, [
            'password' => '12345',
            'password_confirmation' => '12345',
        ]));

        $short->assertStatus(422);
        $this->assertArrayHasKey('password', $short->json());

        $this->postJson('/api/auth/register', array_merge($base, [
            'email' => 'password-ok-'.uniqid().'@example.com',
            'phone' => '05'.random_int(100000000, 999999999),
            'password' => '123456',
            'password_confirmation' => '123456',
        ]))->assertCreated();

        $this->assertSame(1, User::query()->where('email', 'like', 'password-ok-%')->count());
    }

    public function test_admin_settings_do_not_expose_stored_secrets(): void
    {
        config(['services.paystack.secret_key' => 'super-secret-paystack-value']);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->getJson('/api/admin/settings')
            ->assertOk();

        $this->assertStringNotContainsString('super-secret-paystack-value', $response->getContent());
        $response->assertJsonMissingPath('paystack_secret_key');
        $response->assertJsonPath('smtp_password', '');
        $response->assertJsonPath('paystack_secret_key_set', true);
    }

    public function test_legacy_admin_service_api_path_is_gone(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->adminToken())
            ->getJson('/api/admin/services')
            ->assertNotFound();
    }

    public function test_legacy_frontend_and_shop_admin_code_is_removed(): void
    {
        $this->assertFileDoesNotExist(resource_path('views/bundles.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/layouts/app.blade.php'));
        $this->assertFileDoesNotExist(app_path('Shop/Models/Admin.php'));
        $this->assertFileDoesNotExist(app_path('Shop/Models/AdminAuditLog.php'));
        $this->assertFileDoesNotExist(app_path('Shop/Services/AuditService.php'));
    }
}
