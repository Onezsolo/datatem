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
}
