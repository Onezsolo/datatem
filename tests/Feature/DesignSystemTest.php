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
}
