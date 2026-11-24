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

        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2 lg:hidden" aria-label="{{ \App\Support\Brand::name() }} home">
            <img src="{{ \App\Support\Brand::logoUrl() }}" alt="" width="40" height="40" class="h-9 w-9 rounded-control bg-surface object-contain p-0.5">
            <span class="text-sm font-extrabold tracking-[0.06em] text-brand-navy">{{ \Illuminate\Support\Str::upper(\App\Support\Brand::name()) }}</span>
        </a>

        <h1 class="hidden min-w-0 truncate text-lg font-extrabold tracking-tight text-brand-navy lg:block">{{ $title ?? 'Dashboard' }}</h1>

        <div class="ms-auto flex items-center gap-2">
            <span class="hidden text-sm font-bold text-brand-navy sm:inline" x-data x-cloak x-text="'GHS ' + Number($store.auth.user.balance || 0).toFixed(2)"></span>

            <a href="{{ route('notifications') }}" class="inline-flex h-10 w-10 items-center justify-center rounded-control border border-line text-brand-navy hover:bg-surface-muted" aria-label="Notifications">
                <x-ui.icon name="more" :size="18" />
            </a>

            <a href="{{ route('profile') }}" class="hidden min-h-10 items-center gap-2 rounded-control border border-line px-3 text-sm font-semibold text-brand-navy hover:bg-surface-muted sm:inline-flex" x-data x-cloak>
                <x-ui.icon name="help" :size="16" />
                <span class="max-w-[10rem] truncate" x-text="$store.auth.user.full_name || 'Account'"></span>
            </a>

            <button
                type="button"
                class="inline-flex min-h-10 items-center gap-1.5 rounded-control border border-line px-3 text-sm font-semibold text-brand-navy hover:bg-surface-muted"
                x-data
                @click="if (confirm('Sign out of this account?')) $store.auth.logout()"
            >
                Sign out
            </button>
        </div>
    </div>
</header>
