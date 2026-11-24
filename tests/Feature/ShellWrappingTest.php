<?php

namespace Tests\Feature;

use Tests\TestCase;

class ShellWrappingTest extends TestCase
{
    public function test_member_pages_render_inside_member_shell(): void
    {
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
