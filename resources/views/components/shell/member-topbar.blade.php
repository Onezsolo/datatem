@php
    $memberCartUrl = request()->is('shop*') && \Illuminate\Support\Facades\Route::has('shop.cart.show') ? route('shop.cart.show') : null;
    $memberCartCount = 0;

    if ($memberCartUrl && \Illuminate\Support\Facades\Schema::hasTable('shop_carts')) {
        $memberCartCount = app(\App\Shop\Services\CartService::class)->count(request());
    }
@endphp
@props(['title' => null])

<header class="sticky top-0 z-40 border-b border-line bg-surface">
    <div class="container-page flex min-h-14 items-center gap-3 py-2">
        <button
            type="button"
            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-control border border-line text-brand-navy hover:bg-surface-muted lg:hidden"
            aria-label="Open navigation menu"
            aria-controls="mobile-member-nav"
            :aria-expanded="mobileSidebarOpen"
            @click="mobileSidebarOpen = true"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>

        <a href="{{ url('/dashboard') }}" class="flex shrink-0 items-center gap-2 lg:hidden" aria-label="{{ \App\Support\Brand::name() }} home">
            <img src="{{ \App\Support\Brand::logoUrl() }}" alt="" width="40" height="40" class="h-9 w-9 rounded-control bg-surface object-contain p-0.5">
            <span class="text-sm font-extrabold tracking-[0.06em] text-brand-navy">{{ \Illuminate\Support\Str::upper(\App\Support\Brand::name()) }}</span>
        </a>

        <h1 class="hidden min-w-0 truncate text-lg font-extrabold tracking-tight text-brand-navy lg:block">{{ $title ?? 'Dashboard' }}</h1>

        <div class="ms-auto flex items-center gap-2">
            <span class="hidden text-sm font-bold text-brand-navy sm:inline" x-data x-cloak x-text="'GHS ' + Number($store.auth.user.balance || 0).toFixed(2)"></span>

            @if ($memberCartUrl)
                <a
                    href="{{ $memberCartUrl }}"
                    class="inline-flex min-h-10 items-center gap-1.5 rounded-control border border-line px-3 text-sm font-bold text-brand-navy hover:bg-surface-muted"
                    aria-label="Cart, {{ $memberCartCount }} {{ \Illuminate\Support\Str::plural('item', $memberCartCount) }}"
                >
                    <x-ui.icon name="cart" :size="16" />
                    <span class="hidden sm:inline">Cart</span>
                    @if ($memberCartCount > 0)
                        <span class="inline-flex min-h-5 min-w-5 items-center justify-center rounded-control bg-brand-red-strong px-1 text-xs font-bold text-white" aria-hidden="true">{{ $memberCartCount }}</span>
                    @endif
                </a>
            @endif
            <a href="{{ route('notifications') }}" class="inline-flex h-10 w-10 items-center justify-center rounded-control border border-line text-brand-navy hover:bg-surface-muted" aria-label="Notifications">
                <x-ui.icon name="more" :size="18" />
            </a>

            <a href="{{ route('profile') }}" class="hidden min-h-10 items-center gap-2 rounded-control border border-line px-3 text-sm font-semibold text-brand-navy hover:bg-surface-muted sm:inline-flex" x-data x-cloak>
                <x-ui.icon name="help" :size="16" />
                <span class="max-w-[10rem] truncate" x-text="$store.auth.user.full_name || 'Account'"></span>
            </a>

            <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-control border border-line text-brand-navy hover:bg-surface-muted"
                x-data
                @click="if (confirm('Sign out of this account?')) $store.auth.logout()"
                aria-label="Sign out"
                title="Sign out"
            >
                <x-ui.icon name="logout" :size="18" />
            </button>
        </div>
    </div>
</header>
