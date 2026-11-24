<?php

namespace Tests\Unit;

use App\Support\Brand;
use Tests\TestCase;

class BrandTest extends TestCase
{
    public function test_brand_name_falls_back_to_app_name(): void
    {
        $this->assertSame(config('app.name'), Brand::name());
    }

    public function test_brand_asset_urls_are_absolute(): void
    {
        $this->assertStringStartsWith('http', Brand::logoUrl());
        $this->assertStringStartsWith('http', Brand::faviconUrl());
    }
}
