<?php

namespace Tests\Feature;

use Tests\TestCase;

class TemplateHardeningTest extends TestCase
{
    public function test_security_headers_are_present(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_health_endpoint_responds(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_application_timezone_is_ghana(): void
    {
        $this->assertSame('Africa/Accra', config('app.timezone'));
    }
}
