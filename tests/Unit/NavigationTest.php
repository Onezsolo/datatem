<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\FeatureSettings;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_navigation_is_home_only_for_guests(): void
    {
        $items = Navigation::public();

        $this->assertSame(['Home', 'About'], array_column($items, 'label'));

        foreach ($items as $item) {
            $this->assertNotSame('', $item['label']);
            $this->assertNotSame('#', $item['url'], "Missing URL for {$item['label']}.");
            $this->assertStringStartsWith('http', $item['url']);
        }
    }

    public function test_public_navigation_shows_feature_links_when_signed_in(): void
    {
        $this->actingAs(User::create([
            'full_name' => 'Public Nav User',
            'email' => 'public-nav@example.com',
            'phone' => '0244000091',
            'password' => bcrypt('password'),
        ]));

        $items = Navigation::public();

        $this->assertSame(['Home', 'About', 'Super Mall', 'Bundles', 'Stores'], array_column($items, 'label'));

        foreach ($items as $item) {
            $this->assertNotSame('', $item['label']);
            $this->assertNotSame('#', $item['url'], "Missing URL for {$item['label']}.");
            $this->assertStringStartsWith('http', $item['url']);
        }
    }

    public function test_member_navigation_has_expected_groups(): void
    {
        $groups = Navigation::member();

        $this->assertSame(['Overview', 'Services', 'Orders', 'Data earnings', 'Profile'], array_keys($groups));

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

    public function test_guest_bottom_navigation_shows_home_and_sign_in(): void
    {
        $this->assertSame(['Home', 'Sign in'], array_column(Navigation::bottom(), 'label'));
    }

    public function test_member_bottom_navigation_has_four_items(): void
    {
        $this->actingAs(User::create([
            'full_name' => 'Bottom Nav User',
            'email' => 'bottom-nav@example.com',
            'phone' => '0244000092',
            'password' => bcrypt('password'),
        ]));

        $this->assertSame(['Home', 'Super Mall', 'Bundles', 'Profile'], array_column(Navigation::bottom(), 'label'));
    }
    public function test_authenticated_home_links_point_to_account(): void
    {
        $user = User::create([
            'full_name' => 'Home User',
            'email' => 'nav-home@example.com',
            'phone' => '0244000090',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        $public = Navigation::public();
        $this->assertSame(url('/dashboard'), $public[0]['url']);

        $bottom = Navigation::bottom();
        $this->assertSame(url('/dashboard'), $bottom[0]['url']);
        $this->assertSame(route('profile'), $bottom[3]['url']);
    }

    public function test_guest_home_links_point_to_public_home(): void
    {
        $public = Navigation::public();
        $this->assertSame(route('home'), $public[0]['url']);
        $this->assertSame(route('about.index'), $public[1]['url']);

        $bottom = Navigation::bottom();
        $this->assertSame(route('home'), $bottom[0]['url']);
        $this->assertSame(route('login'), $bottom[1]['url']);
    }

    public function test_mall_only_mode_puts_shop_features_in_navigation(): void
    {
        $this->actingAs(User::create([
            'full_name' => 'Mall Nav User',
            'email' => 'mall-nav@example.com',
            'phone' => '0244000093',
            'password' => bcrypt('password'),
        ]));

        FeatureSettings::set(array_merge(
            array_fill_keys(array_keys(FeatureSettings::definitions()), false),
            ['shop' => true, 'shop_orders' => true, 'wallet' => true]
        ));

        $labels = collect(Navigation::member())->flatten(1)->pluck('label');

        $this->assertTrue($labels->contains('Super Mall'));
        $this->assertTrue($labels->contains('Cart'));
        $this->assertTrue($labels->contains('Shop orders'));
        $this->assertFalse($labels->contains('Data bundles'));
        $this->assertFalse($labels->contains('WAEC PINs'));

        $this->assertSame(
            ['Home', 'Super Mall', 'Cart', 'Profile'],
            array_column(Navigation::bottom(), 'label')
        );
    }

    public function test_shop_and_cart_have_distinct_active_states(): void
    {
        $user = User::create([
            'full_name' => 'Nav Shopper',
            'email' => 'nav-shopper-'.uniqid().'@example.com',
            'phone' => '05'.random_int(100000000, 999999999),
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);

        $this->get('/shop/cart')->assertOk();

        $active = collect(Navigation::member())->flatten(1)->filter(fn ($item) => $item['active'])->pluck('label');

        $this->assertTrue($active->contains('Cart'));
        $this->assertFalse($active->contains('Super Mall'));

        $this->get('/shop')->assertOk();

        $active = collect(Navigation::member())->flatten(1)->filter(fn ($item) => $item['active'])->pluck('label');

        $this->assertTrue($active->contains('Super Mall'));
        $this->assertFalse($active->contains('Cart'));
    }}
