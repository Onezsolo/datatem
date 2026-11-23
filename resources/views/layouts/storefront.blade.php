<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    @php
        $siteName = \App\Services\SettingsService::get('site_name', config('app.name', 'Data'));
        $siteDescription = \App\Services\SettingsService::get('site_description', 'Buy mobile data bundles. No account needed. Pay with MoMo.');
        $siteFaviconUrl = \App\Services\SettingsService::getFaviconUrl();
        $siteLogoUrl = \App\Services\SettingsService::getLogoUrl();
    @endphp
    <title>@yield('title', $store_name ?? 'Store') – {{ $siteName }}</title>
    <meta name="description" content="@yield('meta_description', $siteDescription)">
    <meta name="robots" content="@yield('meta_robots', 'index, follow')">

    <link rel="icon" type="image/png" href="{{ $siteFaviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $siteFaviconUrl }}">

    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('og_title', ($store_name ?? 'Store') . ' – Data Bundles')">
    <meta property="og:description" content="@yield('og_description', $siteDescription)">
    <meta property="og:image" content="{{ url($siteLogoUrl) }}">
    <meta property="og:site_name" content="{{ $store_name ?? $siteName }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @php
        $themePrimary = \App\Services\ThemeColorService::getPrimaryShades();
        $themeGradientStart = \App\Services\ThemeColorService::getGradientStart();
        $themeGradientEnd = \App\Services\ThemeColorService::getGradientEnd();
    @endphp
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('auth', {
                user: JSON.parse(localStorage.getItem('user') || '{}'),
                token: localStorage.getItem('token')
            });
        });
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] },
                    colors: {
                        primary: @json($themePrimary),
                        mtn: { base: '#FFCC00', dark: '#E6B800' },
                        airteltigo: { base: '#9333ea', dark: '#6b21a8' },
                        telecel: { base: '#FF0000', dark: '#CC0000' }
                    }
                }
            }
        };
    </script>
    <style>
        [x-cloak] { display: none !important; }
        .glass { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.4); }
        .gradient-text { background: linear-gradient(135deg, {{ $themeGradientStart }} 0%, {{ $themeGradientEnd }} 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body class="h-full text-slate-900 overflow-x-hidden bg-slate-50">
    {{-- Storefront-only navbar: store brand + WhatsApp. No main site login/dashboard. --}}
    <header class="sticky top-0 z-50 glass border-b border-slate-200/60 shadow-sm">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 sm:h-18">
                <a href="{{ url('/store/' . ($slug ?? '')) }}" class="flex items-center gap-3 shrink-0">
                    @php $storeDisplay = $store_name ?? 'Store'; $initial = mb_substr($storeDisplay, 0, 1); @endphp
                    <div class="w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center text-primary-600 font-black text-lg">{{ $initial }}</div>
                    <span class="text-xl font-black text-slate-900 truncate max-w-[180px] sm:max-w-none" title="{{ e($storeDisplay) }}">{{ $storeDisplay }}</span>
                </a>
                <div class="flex items-center gap-3">
                    @if(!empty($store_whatsapp_url))
                    <a href="{{ e($store_whatsapp_url) }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-[#25D366] hover:bg-[#20bd5a] transition-all shadow-md">
                        <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.414 0 0 5.414 0 12.05c0 2.123.551 4.2 1.6 6.04L0 24l6.101-1.601A11.77 11.77 0 0012.05 24.1c6.636 0 12.05-5.414 12.05-12.05 0-3.212-1.25-6.231-3.513-8.5zm-.002 0z"/></svg>
                        <span class="hidden sm:inline">Chat on WhatsApp</span>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <main class="min-h-[calc(100vh-5rem)]">
        @yield('content')
    </main>

    {{-- Minimal footer: just powered-by for storefront --}}
    <footer class="py-6 border-t border-slate-100 bg-white/50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-xs text-slate-400 font-medium">Powered by <a href="{{ url('/') }}" class="text-primary-500 hover:text-primary-600 font-bold">{{ \App\Services\SettingsService::get('site_name', 'R2BMarket') }}</a></p>
        </div>
    </footer>
</body>
</html>
