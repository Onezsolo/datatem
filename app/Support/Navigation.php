<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

final class Navigation
{
    /**
     * @return array<int, array{label: string, url: string, route: string|null, active: bool, icon: string|null}>
     */
    public static function public(): array
    {
        return array_map(fn (array $item) => self::item($item), [
            ['label' => 'Home', 'route' => 'home', 'pattern' => 'home'],
            ['label' => 'Shop', 'url' => url('/shop'), 'pattern' => 'shop*'],
            ['label' => 'Bundles', 'route' => 'bundles', 'pattern' => 'bundles'],
            ['label' => 'Stores', 'route' => 'store.landing', 'pattern' => 'store*'],
            ['label' => 'Support', 'route' => 'support', 'pattern' => 'support'],
        ]);
    }

    /**
     * @return array<string, array<int, array{label: string, url: string, route: string|null, active: bool, icon: string|null}>>
     */
    public static function member(): array
    {
        return [
            'Overview' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Dashboard', 'url' => url('/dashboard'), 'pattern' => 'dashboard', 'icon' => 'home'],
                ['label' => 'Shop', 'url' => url('/shop'), 'pattern' => 'shop*', 'icon' => 'bag'],
                ['label' => 'Buy data', 'route' => 'bundles', 'pattern' => 'bundles', 'icon' => 'phone'],
            ]),
            'Orders' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Orders', 'route' => 'orders.index', 'pattern' => 'orders', 'icon' => 'truck'],
                ['label' => 'Shop orders', 'route' => 'orders.shop', 'pattern' => 'orders/shop', 'icon' => 'bag'],
                ['label' => 'Wallet', 'route' => 'topup', 'pattern' => 'topup*', 'icon' => 'cart'],
            ]),
            'Data earnings' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Data referral', 'route' => 'referral', 'pattern' => 'referral', 'icon' => 'check'],
                ['label' => 'Data reseller', 'route' => 'reseller', 'pattern' => 'reseller*', 'icon' => 'grid'],
            ]),
            'Account' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Notifications', 'route' => 'notifications', 'pattern' => 'notifications', 'icon' => 'more'],
                ['label' => 'Profile', 'route' => 'profile', 'pattern' => 'profile', 'icon' => 'help'],
                ['label' => 'Support', 'route' => 'support', 'pattern' => 'support', 'icon' => 'help'],
            ]),
        ];
    }

    /**
     * @return array<int, array{label: string, url: string, active: bool, icon: string}>
     */
    public static function bottom(): array
    {
        return [
            ['label' => 'Home', 'url' => route('home'), 'active' => request()->routeIs('home'), 'icon' => 'home'],
            ['label' => 'Shop', 'url' => url('/shop'), 'active' => request()->is('shop*'), 'icon' => 'bag'],
            ['label' => 'Bundles', 'url' => route('bundles'), 'active' => request()->routeIs('bundles'), 'icon' => 'phone'],
            ['label' => 'Account', 'url' => url('/dashboard'), 'active' => request()->is('dashboard'), 'icon' => 'help'],
        ];
    }

    /**
     * @return array<string, array<int, array{label: string, url: string, route: string|null, active: bool, icon: string|null}>>
     */
    public static function admin(): array
    {
        return [
            'Overview' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'pattern' => 'admin/dashboard', 'icon' => 'home'],
                ['label' => 'Shop catalog', 'route' => 'admin.shop', 'pattern' => 'admin/shop', 'icon' => 'bag'],
            ]),
            'Data' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Bundles', 'route' => 'admin.packages', 'pattern' => 'admin/packages', 'icon' => 'phone'],
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
