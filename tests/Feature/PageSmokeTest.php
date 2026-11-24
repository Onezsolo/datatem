<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PageSmokeTest extends TestCase
{
    public function test_home_route_name_exists(): void
    {
        $this->assertSame('/', route('home', absolute: false));
    }

    public function test_public_pages_render(): void
    {
        foreach (['/', '/bundles', '/login', '/signup', '/support'] as $uri) {
            $this->get($uri)->assertOk();
        }
    }

    public function test_pilot_pages_use_unified_shell_without_external_cdns(): void
    {
        foreach (['/', '/bundles', '/login', '/signup'] as $uri) {
            $response = $this->get($uri);

            $response->assertOk()
                ->assertSee('Skip to main content')
                ->assertSee('id="main-content"', false)
                ->assertSee('Bottom navigation')
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

    public function test_bundles_page_keeps_purchase_ui_and_networks(): void
    {
        $this->get('/bundles')
            ->assertOk()
            ->assertSee('x-data', false)
            ->assertSee('MTN')
            ->assertSee('AirtelTigo')
            ->assertSee('Telecel');
    }

    public function test_home_page_links_to_data_and_shop(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Buy data')
            ->assertSee('href="'.route('bundles').'"', false)
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
