<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_has_standard_seo_metadata(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Data bundles and online shopping in Ghana', false)
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('name="robots" content="index,follow"', false)
            ->assertSee('property="og:type" content="website"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:description"', false)
            ->assertSee('property="og:url"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('name="twitter:card"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('og-default.png', false);

        $this->assertFileExists(public_path('assets/og-default.png'));
    }

    public function test_auth_pages_are_noindex(): void
    {
        $pages = [
            '/login' => 'noindex,follow',
            '/signup' => 'noindex,follow',
            '/maintenance' => 'noindex,nofollow',
        ];

        foreach ($pages as $uri => $robots) {
            $this->get($uri)
                ->assertOk()
                ->assertSee('name="robots" content="'.$robots.'"', false);
        }
    }

    public function test_member_and_admin_shells_are_noindex(): void
    {
        $member = User::create([
            'full_name' => 'SEO Member',
            'email' => 'seo-member@example.com',
            'phone' => '0244000080',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($member)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false);

        $admin = User::create([
            'full_name' => 'SEO Admin',
            'email' => 'seo-admin@example.com',
            'phone' => '0244000081',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false);
    }

    public function test_sitemap_lists_only_public_pages(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->assertSee('<loc>'.url('/').'</loc>', false)
            ->assertSee(route('legal.privacy'), false)
            ->assertSee(route('legal.terms'), false)
            ->assertSee(route('legal.returns'), false)
            ->assertSee(route('legal.contact'), false)
            ->assertDontSee(url('/shop'), false)
            ->assertDontSee(url('/dashboard'), false);
    }

    public function test_robots_disallows_private_areas_and_points_to_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('User-agent: *', false)
            ->assertSee('Disallow: /admin', false)
            ->assertSee('Disallow: /shop', false)
            ->assertSee('Sitemap: '.url('/sitemap.xml'), false);
    }
}
