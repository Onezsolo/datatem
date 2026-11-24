<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthCookieTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_sets_http_only_auth_cookie_for_web_shop_auth(): void
    {
        $user = User::create([
            'full_name' => 'Cookie User',
            'email' => 'cookie@example.com',
            'phone' => '0244000002',
            'password' => bcrypt('password'),
        ]);

        $this->postJson('/api/auth/login', [
            'login' => $user->email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertCookie('auth_token');
    }
}
