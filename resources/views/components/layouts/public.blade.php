@props([
    'title' => null,
    'description' => null,
    'cartCount' => 0,
    'brand' => null,
])

@php
    $navigation = \App\Support\Navigation::public();
    $cartUrl = request()->is('shop*') && \Illuminate\Support\Facades\Route::has('shop.cart.show') ? route('shop.cart.show') : null;
    if ($cartCount === 0 && $cartUrl && \Illuminate\Support\Facades\Schema::hasTable('shop_carts')) {
        $cartCount = app(\App\Shop\Services\CartService::class)->count(request());
    }
@endphp

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' - '.\App\Support\Brand::name() : \App\Support\Brand::name() }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    <link rel="icon" type="image/png" href="{{ \App\Support\Brand::faviconUrl() }}">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-full bg-surface pb-16 text-ink lg:pb-0">
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <div data-offline-banner class="hidden border-b border-brand-red-strong bg-brand-red-strong px-4 py-2 text-center text-sm font-semibold text-white" role="status">
        You are offline. Cached pages remain available; cart and checkout need a connection.
    </div>

    <x-ui.header
        :navigation="$navigation"
        :cart-count="$cartCount"
        :cart-url="$cartUrl"
        :brand="$brand"
    />

    @if (session('status'))
        <div class="border-b border-line bg-surface-muted" role="status">
            <div class="container-page py-3 text-sm font-semibold text-brand-navy">{{ session('status') }}</div>
        </div>
    @endif

    @if (session('warning'))
        <div class="border-b border-brand-red-strong bg-surface" role="alert">
            <div class="container-page flex items-start gap-2 py-3 text-sm text-brand-navy">
                <span class="mt-1 inline-block h-2 w-2 shrink-0 rounded-control bg-brand-red-strong" aria-hidden="true"></span>
                <span>{{ session('warning') }}</span>
            </div>
        </div>
    @endif

    <main id="main-content">
        {{ $slot }}
    </main>

    <x-shell.public-footer />
    <x-shell.bottom-nav :cart-count="$cartCount" />
    <x-shell.alert-modal />
</body>
</html>
