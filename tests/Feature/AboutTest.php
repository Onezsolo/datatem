<?php

namespace Tests\Feature;

use Tests\TestCase;

class AboutTest extends TestCase
{
    public function test_about_index_lists_company_information_with_seo(): void
    {
        $this->get('/about')
            ->assertOk()
            ->assertSee('Company information and updates')
            ->assertSee('About the business')
            ->assertSee('How orders and delivery work')
            ->assertSee('Payments, accounts and security')
            ->assertSee('rel="canonical"', false)
            ->assertSee('property="og:title"', false)
            ->assertDontSee('Blog');
    }

    public function test_about_page_renders_article_and_structured_data(): void
    {
        $this->get('/about/about-the-business')
            ->assertOk()
            ->assertSee('About the business')
            ->assertSee('What we do')
            ->assertSee('property="og:type" content="article"', false)
            ->assertSee('"@type":"Article"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('Create free account')
            ->assertDontSee('Blog');
    }

    public function test_unknown_about_page_returns_404(): void
    {
        $this->get('/about/not-a-page')->assertNotFound();
    }

    public function test_sitemap_includes_about_pages(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('about.index'), false)
            ->assertSee(route('about.show', 'about-the-business'), false)
            ->assertSee(route('about.show', 'payments-accounts-and-security'), false)
            ->assertDontSee(url('/blog'), false);
    }

    public function test_about_is_linked_from_the_public_site(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.route('about.index').'"', false)
            ->assertSee('href="'.route('about.show', 'about-the-business').'"', false)
            ->assertSee('Company information and updates')
            ->assertDontSee('Blog');
    }
}
