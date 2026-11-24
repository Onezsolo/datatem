@props([
    'title' => null,
    'description' => null,
    'canonical' => null,
    'robots' => 'index,follow',
    'image' => null,
    'type' => 'website',
    'schema' => null,
    'cartCount' => 0,
    'brand' => null,
])

@php
    $navigation = \App\Support\Navigation::public();
    $homeUrl = auth()->check() ? url('/dashboard') : route('home');
    $cartUrl = request()->is('shop*') && \Illuminate\Support\Facades\Route::has('shop.cart.show') ? route('shop.cart.show') : null;
    if ($cartCount === 0 && $cartUrl && \Illuminate\Support\Facades\Schema::hasTable('shop_carts')) {
        $cartCount = app(\App\Shop\Services\CartService::class)->count(request());
    }

    $brandName = \App\Support\Brand::name();
    $pageTitle = $title ? $title.' - '.$brandName : $brandName;
    $metaDescription = $description ?: 'One account for data bundles, everyday shopping and digital services in Ghana.';
    $canonicalUrl = $canonical ?: url()->current();
    $ogImage = $image ?: asset('assets/og-default.png');
    $structuredData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                'name' => $brandName,
                'url' => url('/'),
                'logo' => \App\Support\Brand::logoUrl(),
            ],
            [
                '@type' => 'WebSite',
                'name' => $brandName,
                'url' => url('/'),
            ],
            [
                '@type' => 'WebPage',
                'name' => $pageTitle,
                'description' => $metaDescription,
                'url' => $canonicalUrl,
            ],
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="{{ $robots }}">
    <meta name="theme-color" content="#0A2038">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    <meta property="og:type" content="{{ $type }}">
    <meta property="og:site_name" content="{{ $brandName }}">
    <meta property="og:locale" content="en_GH">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $ogImage }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <link rel="icon" type="image/svg+xml" href="{{ \App\Support\Brand::faviconUrl() }}">
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @if ($schema)
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endif
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @include('partials.theme-style')
</head>
<body class="min-h-full bg-surface text-ink">
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <div data-offline-banner class="hidden border-b border-brand-red-strong bg-brand-red-strong px-4 py-2 text-center text-sm font-semibold text-white" role="status">
        You are offline. Cached pages remain available; cart and checkout need a connection.
    </div>

    <x-ui.header
        :navigation="$navigation"
        :cart-count="$cartCount"
        :cart-url="$cartUrl"
        :brand="$brand"
        :home-url="$homeUrl"
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
    <x-shell.alert-modal />
</body>
</html>
