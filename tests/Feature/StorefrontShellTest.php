<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_landing_uses_public_shell(): void
    {
        $this->get('/store')
            ->assertOk()
            ->assertSee('Visit a store')
            ->assertSee('Primary navigation')
            ->assertSee('Bottom navigation')
            ->assertDontSee('cdn.tailwindcss.com', false)
            ->assertDontSee('fonts.googleapis.com', false);
    }

    public function test_reseller_storefront_uses_public_shell(): void
    {
        $this->get('/store/demo-store')
            ->assertOk()
            ->assertSee('Mobile navigation')
            ->assertSee('Bottom navigation')
            ->assertDontSee('cdn.tailwindcss.com', false)
            ->assertDontSee('unpkg.com', false)
            ->assertDontSee('fonts.googleapis.com', false);
    }

    public function test_spin_preview_uses_compiled_assets_only(): void
    {
        $this->actingAs(User::create([
            'full_name' => 'Spin Admin',
            'email' => 'spin-admin@example.com',
            'phone' => '0244000021',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]));

        $this->get('/admin/spin-preview')
            ->assertOk()
            ->assertSee('build/assets', false)
            ->assertDontSee('cdn.tailwindcss.com', false)
            ->assertDontSee('jsdelivr.net', false)
            ->assertDontSee('fonts.googleapis.com', false);
    }
}
