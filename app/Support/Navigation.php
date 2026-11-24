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
                ['label' => 'Orders', 'route' => 'orders.index', 'pattern' => 'orders*', 'icon' => 'truck'],
                ['label' => 'Wallet', 'route' => 'topup', 'pattern' => 'topup*', 'icon' => 'cart'],
            ]),
            'Earnings' => array_map(fn (array $item) => self::item($item), [
                ['label' => 'Referral', 'route' => 'referral', 'pattern' => 'referral', 'icon' => 'check'],
                ['label' => 'Reseller', 'route' => 'reseller', 'pattern' => 'reseller*', 'icon' => 'grid'],
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
