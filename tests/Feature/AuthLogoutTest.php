<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLogoutTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'full_name' => 'Logout User',
            'email' => 'logout@example.com',
            'phone' => '0244000061',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_authenticated_user_is_redirected_away_from_login_and_signup(): void
    {
        $this->actingAs($this->user());

        $this->get('/login')->assertRedirect('/dashboard');
        $this->get('/signup')->assertRedirect('/dashboard');
    }

    public function test_logout_clears_the_auth_cookie_and_invalidates_the_token(): void
    {
        $user = $this->user();

        $login = $this->postJson('/api/auth/login', [
            'login' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $token = $login->json('access_token');

        $this->withCookie('auth_token', $token)
            ->get('/dashboard')
            ->assertOk();

        $this->withCookie('auth_token', $token)
            ->post('/logout')
            ->assertOk()
            ->assertCookieExpired('auth_token');

    }
}
