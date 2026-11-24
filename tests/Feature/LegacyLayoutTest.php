<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegacyLayoutTest extends TestCase
{
    public function test_member_pages_use_compiled_assets_instead_of_cdns(): void
    {
        $response = $this->get('/dashboard');

        $response->assertOk()
            ->assertSee('build/assets', false)
            ->assertDontSee('cdn.tailwindcss.com', false)
            ->assertDontSee('unpkg.com', false)
            ->assertDontSee('fonts.googleapis.com', false);
    }
}
