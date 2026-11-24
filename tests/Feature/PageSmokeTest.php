<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PageSmokeTest extends TestCase
{
    use RefreshDatabase;
    public function test_home_route_name_exists(): void
    {
        $this->assertSame('/', route('home', absolute: false));
    }

    public function test_public_pages_render(): void
    {
        foreach (['/', '/login', '/signup'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_pilot_pages_use_unified_shell_without_external_cdns(): void
    {
        foreach (['/', '/login', '/signup'] as $uri) {
            $response = $this->get($uri);

            $response->assertOk()
                ->assertSee('Skip to main content')
                ->assertSee('id="main-content"', false)
                ->assertSee('Primary navigation')
                ->assertDontSee('Bottom navigation')
                ->assertDontSee('cdn.tailwindcss.com', false)
                ->assertDontSee('unpkg.com', false)
                ->assertDontSee('fonts.googleapis.com', false);
        }
    }

    public function test_login_page_has_named_form_fields(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }

    public function test_signup_page_has_named_form_fields(): void
    {
        $this->get('/signup')
            ->assertOk()
            ->assertSee('name="full_name"', false)
            ->assertSee('name="phone"', false);
    }

    public function test_legacy_bundles_route_redirects_to_services_data(): void
    {
        $this->get('/bundles')->assertRedirect(route('services.data'));
    }

    public function test_guest_home_hides_member_feature_links(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Sign in')
            ->assertSee('Create free account')
            ->assertSee('Get started')
            ->assertDontSee('href="'.route('services.data').'"', false)
            ->assertDontSee('href="'.url('/shop').'"', false)
            ->assertDontSee('Super Mall');
    }

    public function test_signed_in_home_links_to_data_and_shop(): void
    {
        $this->actingAs(User::create([
            'full_name' => 'Home Link User',
            'email' => 'home-links@example.com',
            'phone' => '0244000070',
            'password' => bcrypt('password'),
        ]));

        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.route('services.data').'"', false)
            ->assertSee('href="'.url('/shop').'"', false);
    }

    public function test_route_names_are_unique(): void
    {
        $duplicates = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route) => $route->getName())
            ->filter()
            ->duplicates();

        $this->assertCount(0, $duplicates, 'Duplicate route names: '.$duplicates->implode(', '));
    }
}
