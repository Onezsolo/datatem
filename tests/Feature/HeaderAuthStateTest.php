<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderAuthStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_public_shell_shows_sign_in_and_not_sign_out(): void
    {
        $html = $this->blade('<x-layouts.public>Body</x-layouts.public>');

        $html->assertSee('Sign in')
            ->assertSee('Get started')
            ->assertDontSee('Sign out');
    }

    public function test_authenticated_public_shell_shows_account_and_sign_out_only(): void
    {
        $user = User::create([
            'full_name' => 'Header User',
            'email' => 'header@example.com',
            'phone' => '0244000060',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        $html = $this->blade('<x-layouts.public>Body</x-layouts.public>');

        $html->assertSee('Account')
            ->assertSee('Sign out')
            ->assertDontSee('Sign in');
    }
    public function test_guest_home_shows_signup_cta(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Create free account')
            ->assertSee('Sign in');
    }

    public function test_authenticated_users_still_see_the_home_page(): void
    {
        $user = User::create([
            'full_name' => 'Home User',
            'email' => 'home-auth@example.com',
            'phone' => '0244000062',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertDontSee('Create free account');
    }

    public function test_authenticated_admins_still_see_the_home_page(): void
    {
        $admin = User::create([
            'full_name' => 'Home Admin',
            'email' => 'home-admin@example.com',
            'phone' => '0244000063',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get('/')
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertDontSee('Create free account');
    }
}