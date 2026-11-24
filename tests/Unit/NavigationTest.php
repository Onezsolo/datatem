<?php

namespace Tests\Unit;

use App\Support\Navigation;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    public function test_public_navigation_resolves(): void
    {
        $items = Navigation::public();

        $this->assertCount(4, $items);

        foreach ($items as $item) {
            $this->assertNotSame('', $item['label']);
            $this->assertNotSame('#', $item['url'], "Missing URL for {$item['label']}.");
            $this->assertStringStartsWith('http', $item['url']);
        }
    }

    public function test_member_navigation_has_expected_groups(): void
    {
        $groups = Navigation::member();

        $this->assertSame(['Overview', 'Orders', 'Services', 'Data earnings', 'Account'], array_keys($groups));

        $count = 0;

        foreach ($groups as $items) {
            foreach ($items as $item) {
                $this->assertNotSame('#', $item['url'], "Missing URL for {$item['label']}.");
                $count++;
            }
        }

        $this->assertSame(17, $count);

        $labels = collect($groups)->flatten(1)->pluck('label');
        $this->assertTrue($labels->contains('Orders'));
        $this->assertTrue($labels->contains('Shop orders'));
    }

    public function test_bottom_navigation_has_four_items(): void
    {
        $items = Navigation::bottom();

        $this->assertSame(['Home', 'Shop', 'Bundles', 'Account'], array_column($items, 'label'));
    }
}
