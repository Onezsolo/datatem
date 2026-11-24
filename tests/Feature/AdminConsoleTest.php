<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminConsoleTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::create([
            'full_name' => 'Console Admin',
            'email' => 'console-admin@example.com',
            'phone' => '0244000010',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    public function test_guest_is_redirected_from_admin_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirectContains('/login');
    }

    public function test_non_admin_users_are_forbidden(): void
    {
        $user = User::create([
            'full_name' => 'Regular User',
            'email' => 'regular@example.com',
            'phone' => '0244000011',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_admin_pages_render_inside_admin_shell(): void
    {
        $this->actingAs($this->adminUser());

        $pages = [
            '/admin/dashboard',
            '/admin/users',
            '/admin/packages',
            '/admin/orders',
            '/admin/topups',
            '/admin/payments',
            '/admin/support',
            '/admin/notifications',
            '/admin/announcements',
            '/admin/settings',
            '/admin/statistics',
            '/admin/resellers',
            '/admin/affiliates',
            '/admin/shop',
        ];

        foreach ($pages as $uri) {
            $this->get($uri)
                ->assertOk()
                ->assertSee('Admin navigation')
                ->assertSee('Admin bottom navigation')
                ->assertDontSee('cdn.tailwindcss.com', false)
                ->assertDontSee('fonts.googleapis.com', false);
        }
    }

    public function test_welcome_flag_opens_the_admin_mobile_sidebar(): void
    {
        $this->actingAs($this->adminUser())
            ->get('/admin/dashboard?welcome=1')
            ->assertOk()
            ->assertSee('adminSidebarOpen: true', false);
    }
}
