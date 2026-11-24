<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShellWrappingTest extends TestCase
{
    use RefreshDatabase;

    private function actingMember(): static
    {
        $user = User::create([
            'full_name' => 'Shell User',
            'email' => 'shell@example.com',
            'phone' => '0244000040',
            'password' => bcrypt('password'),
        ]);

        return $this->actingAs($user);
    }

    public function test_member_pages_render_inside_member_shell(): void
    {
        $this->actingMember();

        foreach (['/dashboard', '/orders', '/topup', '/referral', '/reseller', '/reseller/prices', '/reseller/withdrawals', '/profile', '/notifications'] as $uri) {
            $response = $this->get($uri);

            $response->assertOk()
                ->assertSee('Member navigation')
                ->assertSee('Bottom navigation')
                ->assertDontSee('cdn.tailwindcss.com', false)
                ->assertDontSee('fonts.googleapis.com', false);
        }
    }

    public function test_public_pages_render_inside_public_shell(): void
    {
        foreach (['/support', '/privacy', '/terms', '/maintenance', '/forgot-password'] as $uri) {
            $response = $this->get($uri);

            $response->assertOk()
                ->assertSee('Primary navigation')
                ->assertSee('Bottom navigation')
                ->assertDontSee('cdn.tailwindcss.com', false)
                ->assertDontSee('fonts.googleapis.com', false);
        }
    }
}
