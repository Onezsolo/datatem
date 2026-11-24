<?php

namespace App\Support;

use App\Services\FeatureSettings;
use Illuminate\Support\Facades\Route;

final class Navigation
{
    /**
     * @return array<int, array{label: string, url: string, route: string|null, active: bool, icon: string|null}>
     */
    public static function public(): array
    {
        $authed = auth()->check();

        return self::filterItems(array_map(fn (array $item) => self::item($item), [
            [
                'label' => 'Home',
                'url' => $authed ? url('/dashboard') : route('home'),
                'pattern' => $authed ? 'dashboard' : 'home',
            ],
            ['label' => 'Super Mall', 'url' => url('/shop'), 'pattern' => 'shop*'],
            ['label' => 'Bundles', 'route' => 'services.data', 'pattern' => 'services/data'],
            ['label' => 'Stores', 'route' => 'store.landing', 'pattern' => 'store*'],
        ]));
    }

    /**
     * @return array<string, array<int, array{label: string, url: string, route: string|null, active: bool, icon: string|null}>>
     */
    public static function member(): array
    {
        $groups = [
            'Overview' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Dashboard', 'url' => url('/dashboard'), 'pattern' => 'dashboard', 'icon' => 'home'],
            ]),
            'Services' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Super Mall', 'url' => url('/shop'), 'pattern' => 'shop*', 'icon' => 'bag'],
                ['label' => 'Cart', 'route' => 'shop.cart.show', 'pattern' => 'shop/cart', 'icon' => 'cart'],
                ['label' => 'Data bundles', 'route' => 'services.data', 'pattern' => 'services/data', 'icon' => 'phone'],
                ['label' => 'WAEC PINs', 'route' => 'services.waec', 'pattern' => 'services/waec', 'icon' => 'check'],
                ['label' => 'Utility bills', 'route' => 'services.utility', 'pattern' => 'services/utility', 'icon' => 'grid'],
                ['label' => 'Subscriptions', 'route' => 'services.subscriptions', 'pattern' => 'services/subscriptions', 'icon' => 'bag'],
                ['label' => 'AFA registration', 'route' => 'services.afa', 'pattern' => 'services/afa', 'icon' => 'help'],
            ]),
            'Orders' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Orders', 'route' => 'orders.index', 'pattern' => 'orders', 'icon' => 'truck'],
                ['label' => 'Shop orders', 'route' => 'orders.shop', 'pattern' => 'orders/shop', 'icon' => 'bag'],
                ['label' => 'Wallet', 'route' => 'topup', 'pattern' => 'topup*', 'icon' => 'cart'],
                ['label' => 'Service orders', 'route' => 'services.orders', 'pattern' => 'services/orders', 'icon' => 'grid'],
            ]),
            'Data earnings' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Data referral', 'route' => 'referral', 'pattern' => 'referral', 'icon' => 'check'],
                ['label' => 'Data reseller', 'route' => 'reseller', 'pattern' => 'reseller*', 'icon' => 'grid'],
            ]),
            'Profile' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Notifications', 'route' => 'notifications', 'pattern' => 'notifications', 'icon' => 'more'],
                ['label' => 'Profile', 'route' => 'profile', 'pattern' => 'profile', 'icon' => 'help'],
                ['label' => 'Support', 'route' => 'support', 'pattern' => 'support', 'icon' => 'help'],
            ]),
        ];

        return self::filterGroups($groups);
    }

    /**
     * @return array<int, array{label: string, url: string, active: bool, icon: string}>
     */
    public static function bottom(): array
    {
        $authed = auth()->check();

        $items = [
            ['label' => 'Home', 'url' => $authed ? url('/dashboard') : route('home'), 'active' => $authed ? request()->is('dashboard') : request()->routeIs('home'), 'icon' => 'home'],
        ];

        if (FeatureSettings::enabled('shop')) {
            $items[] = ['label' => 'Super Mall', 'url' => url('/shop'), 'active' => request()->is('shop*'), 'icon' => 'bag'];
        }

        if (FeatureSettings::enabled('data')) {
            $items[] = ['label' => 'Bundles', 'url' => route('services.data'), 'active' => request()->is('services/data'), 'icon' => 'phone'];
        } elseif (FeatureSettings::enabled('shop')) {
            $items[] = ['label' => 'Cart', 'url' => route('shop.cart.show'), 'active' => request()->is('shop/cart'), 'icon' => 'cart'];
        }

        $items[] = ['label' => 'Profile', 'url' => $authed ? route('profile') : url('/dashboard'), 'active' => $authed ? request()->routeIs('profile') : request()->is('dashboard'), 'icon' => 'help'];

        return self::filterItems($items);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private static function filterItems(array $items): array
    {
        return array_values(array_filter($items, fn (array $item) => self::labelEnabled((string) ($item['label'] ?? ''))));
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $groups
     * @return array<string, array<int, array<string, mixed>>>
     */
    private static function filterGroups(array $groups): array
    {
        $filtered = [];

        foreach ($groups as $name => $items) {
            $items = self::filterItems($items);

            if ($items !== []) {
                $filtered[$name] = $items;
            }
        }

        return $filtered;
    }

    private static function labelEnabled(string $label): bool
    {
        $feature = [
            'Super Mall' => 'shop',
            'Cart' => 'shop',
            'Bundles' => 'data',
            'Stores' => 'reseller',
            'Support' => 'support',
            'Data bundles' => 'data',
            'WAEC PINs' => 'waec',
            'Utility bills' => 'utility',
            'Subscriptions' => 'subscriptions',
            'AFA registration' => 'afa',
            'Orders' => 'orders',
            'Shop orders' => 'shop_orders',
            'Wallet' => 'wallet',
            'Service orders' => 'service_orders',
            'Data referral' => 'referral',
            'Data reseller' => 'reseller',
            'Notifications' => 'notifications',
        ][$label] ?? null;

        return $feature === null || FeatureSettings::enabled($feature);
    }

    /**
     * @return array<string, array<int, array{label: string, url: string, route: string|null, active: bool, icon: string|null}>>
     */
    public static function admin(): array
    {
        if (auth()->check() && auth()->user()->isDeveloper()) {
            return [
                'Developer' => array_map(fn (array $item) => self::item($item), [
                    ['label' => 'Console', 'route' => 'developer.dashboard', 'pattern' => 'developer', 'icon' => 'home'],
                    ['label' => 'Template products', 'route' => 'developer.products', 'pattern' => 'developer/products', 'icon' => 'grid'],
                    ['label' => 'Template packages', 'route' => 'developer.packages', 'pattern' => 'developer/packages', 'icon' => 'grid'],
                    ['label' => 'Web templates', 'route' => 'developer.templates', 'pattern' => 'developer/templates', 'icon' => 'bag'],
                    ['label' => 'Licenses', 'route' => 'developer.licenses', 'pattern' => 'developer/licenses', 'icon' => 'check'],
                ]),
                'Setup' => array_map(fn (array $item) => self::item($item), [
                    ['label' => 'Service sources', 'route' => 'admin.services', 'pattern' => 'admin/services', 'icon' => 'grid'],
                    ['label' => 'Template settings', 'route' => 'admin.settings', 'pattern' => 'admin/settings', 'icon' => 'help'],
                ]),
            ];
        }

        $groups = [
            'Overview' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'pattern' => 'admin/dashboard', 'icon' => 'home'],
                ['label' => 'Shop catalog', 'route' => 'admin.shop', 'pattern' => 'admin/shop', 'icon' => 'bag'],
            ]),
            'Data' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Bundles', 'route' => 'admin.packages', 'pattern' => 'admin/packages', 'icon' => 'phone'],
                ['label' => 'Services', 'route' => 'admin.services', 'pattern' => 'admin/services', 'icon' => 'grid'],
                ['label' => 'Data orders', 'route' => 'admin.orders', 'pattern' => 'admin/orders', 'icon' => 'truck'],
                ['label' => 'Top-ups', 'route' => 'admin.topups', 'pattern' => 'admin/topups', 'icon' => 'cart'],
            ]),
            'Finance' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Finance overview', 'route' => 'admin.statistics', 'pattern' => 'admin/statistics', 'icon' => 'grid'],
                ['label' => 'Payments', 'route' => 'admin.payments', 'pattern' => 'admin/payments', 'icon' => 'check'],
            ]),
            'Growth' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Users', 'route' => 'admin.users', 'pattern' => 'admin/users', 'icon' => 'help'],
                ['label' => 'Resellers', 'route' => 'admin.resellers', 'pattern' => 'admin/resellers', 'icon' => 'bag'],
                ['label' => 'Affiliates', 'route' => 'admin.affiliates', 'pattern' => 'admin/affiliates', 'icon' => 'check'],
                ['label' => 'Announcements', 'route' => 'admin.announcements', 'pattern' => 'admin/announcements', 'icon' => 'more'],
            ]),
            'Support' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Support', 'route' => 'admin.support', 'pattern' => 'admin/support', 'icon' => 'help'],
                ['label' => 'Notifications', 'route' => 'admin.notifications', 'pattern' => 'admin/notifications', 'icon' => 'more'],
            ]),
            'Settings' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Settings', 'route' => 'admin.settings', 'pattern' => 'admin/settings', 'icon' => 'grid'],
            ]),
        ];

        return $groups;
    }

    /**
     * @return array<int, array{label: string, url: string, active: bool, icon: string}>
     */
    public static function adminBottom(): array
    {
        return [
            ['label' => 'Dashboard', 'url' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard'), 'icon' => 'home'],
            ['label' => 'Orders', 'url' => route('admin.orders'), 'active' => request()->routeIs('admin.orders'), 'icon' => 'truck'],
            ['label' => 'Shop', 'url' => route('admin.shop'), 'active' => request()->routeIs('admin.shop'), 'icon' => 'bag'],
            ['label' => 'Settings', 'url' => route('admin.settings'), 'active' => request()->routeIs('admin.settings'), 'icon' => 'grid'],
        ];
    }

    /**
     * @param  array{label: string, route?: string, url?: string, pattern?: string, icon?: string}  $item
     * @return array{label: string, url: string, route: string|null, active: bool, icon: string|null}
     */
    private static function item(array $item): array
    {
        $route = $item['route'] ?? null;
        $url = $route && Route::has($route) ? route($route) : ($item['url'] ?? '#');
        $pattern = $item['pattern'] ?? $route;

        return [
            'label' => $item['label'],
            'url' => $url,
            'route' => $route,
            'active' => $pattern !== null && request()->is($pattern),
            'icon' => $item['icon'] ?? null,
        ];
    }
}
