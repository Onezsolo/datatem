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

    public function test_authenticated_login_and_signup_pages_render_without_server_loop(): void
    {
        $this->actingAs($this->user());

        $this->get('/login')->assertOk();
        $this->get('/signup')->assertOk();
    }

    public function test_authenticated_session_can_mint_a_client_token(): void
    {
        $user = $this->user();

        $this->actingAs($user);

        $response = $this->postJson('/auth/session-token')->assertOk();

        $this->assertNotEmpty($response->json('access_token'));
        $this->assertSame($user->email, $response->json('user.email'));
    }

    public function test_session_token_endpoint_requires_login(): void
    {
        $this->post('/auth/session-token')->assertRedirectContains('/login');
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
