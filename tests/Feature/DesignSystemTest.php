<?php

namespace Tests\Feature;

use Tests\TestCase;

class DesignSystemTest extends TestCase
{
    public function test_design_system_preview_renders_components(): void
    {
        $response = $this->get('/design-system');

        $response->assertOk();
        $response->assertSee('Design system');
        $response->assertSee('Buttons');
        $response->assertSee('Badges');
        $response->assertSee('Carousel');
        $response->assertSee('Bundles');
        $response->assertSee('rounded-control', false);
    }

    public function test_design_system_preview_has_no_external_cdn_assets(): void
    {
        $response = $this->get('/design-system');

        $response->assertDontSee('cdn.tailwindcss.com', false);
        $response->assertDontSee('unpkg.com', false);
        $response->assertDontSee('fonts.googleapis.com', false);
    }

    public function test_public_shell_renders_standard_header_footer_and_nav(): void
    {
        $html = $this->blade('<x-layouts.public>Body content</x-layouts.public>');

        $html->assertSee('Skip to main content')
            ->assertSee('id="main-content"', false)
            ->assertSee('Primary navigation')
            ->assertSee('Sign in')
            ->assertSee('Get started')
            ->assertSee('Create account')
            ->assertSee('Body content')
            ->assertDontSee('Bottom navigation')
            ->assertDontSee('Mobile navigation')
            ->assertDontSee('Open navigation menu')
            ->assertDontSee('Bundles')
            ->assertDontSee('Reseller stores')
            ->assertDontSee('cdn.tailwindcss.com', false)
            ->assertDontSee('unpkg.com', false);
    }

    public function test_member_shell_has_sidebar_and_bottom_nav(): void
    {
        $html = $this->blade('<x-layouts.member title="Dashboard">Body content</x-layouts.member>');

        $html->assertSee('Member navigation')
            ->assertSee('Mobile member navigation')
            ->assertSee('Open navigation menu')
            ->assertSee('Overview')
            ->assertSee('Dashboard')
            ->assertSee('Sign out')
            ->assertSee('lg:hidden', false)
            ->assertSee('Body content');
    }
}
