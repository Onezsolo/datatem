@props([
    'brand' => null,
    'homeUrl' => null,
    'navigation' => [],
    'cartCount' => 0,
    'cartUrl' => null,
    'searchAction' => null,
])

@php
    $brand = $brand ?: \App\Support\Brand::name();
    $homeUrl = $homeUrl ?: url('/');
@endphp

<header class="sticky top-0 z-50 border-b-[3px] border-brand-yellow bg-brand-navy text-white" role="banner" x-data="{ mobileNavOpen: false }">
    <div class="container-page flex flex-wrap items-center gap-x-3 gap-y-2 py-2.5 lg:flex-nowrap">
        <button
            type="button"
            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-control bg-white/10 text-white hover:bg-white/20 focus-visible:outline-brand-yellow lg:hidden"
            aria-label="Open navigation menu"
            aria-controls="mobile-public-nav"
            :aria-expanded="mobileNavOpen"
            @click="mobileNavOpen = true"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>

        <a href="{{ $homeUrl }}" class="flex shrink-0 items-center gap-2.5 rounded-control focus-visible:outline-brand-yellow" aria-label="{{ $brand }} home">
            <img src="{{ \App\Support\Brand::logoUrl() }}" alt="" width="48" height="48" class="h-9 w-9 rounded-control bg-surface object-contain p-1 sm:h-10 sm:w-10">
            <span class="hidden leading-none sm:block">
                <span class="block text-base font-extrabold tracking-[0.06em] text-white">{{ \Illuminate\Support\Str::upper($brand) }}</span>
                <span class="mt-1 block text-[0.5625rem] font-bold tracking-[0.26em] text-brand-yellow sm:text-[0.625rem]">DATA &amp; SHOPPING</span>
            </span>
        </a>

        <nav class="hidden w-full min-w-0 lg:block lg:w-auto lg:flex-1" aria-label="Primary navigation">
            <ul class="flex items-center gap-1 overflow-x-auto pb-1 lg:overflow-visible lg:pb-0">
                @foreach ($navigation as $item)
                    <li>
                        <a
                            href="{{ $item['url'] }}"
                            @if (! empty($item['active'])) aria-current="page" @endif
                            @class([
                                'inline-flex min-h-11 items-center whitespace-nowrap rounded-control px-2.5 text-sm font-semibold text-nav-muted hover:bg-white/10 hover:text-white focus-visible:outline-brand-yellow',
                                'bg-white/10 text-white' => ! empty($item['active']),
                            ])
                        >
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="ms-auto flex items-center gap-2">
            @if ($searchAction)
                <a href="{{ $searchAction }}" class="inline-flex h-11 w-11 items-center justify-center rounded-control bg-white/10 text-white hover:bg-white/20 focus-visible:outline-brand-yellow sm:hidden" aria-label="Search products">
                    <x-ui.icon name="search" :size="18" />
                </a>

                <form action="{{ $searchAction }}" method="GET" role="search" class="hidden items-center gap-1.5 rounded-control bg-surface px-2 sm:flex">
                    <x-ui.icon name="search" :size="16" class="text-brand-navy" />
                    <label for="site-search" class="sr-only">Search products</label>
                    <input
                        id="site-search"
                        type="search"
                        name="q"
                        placeholder="Search products"
                        class="h-11 w-32 border-0 bg-transparent px-1 text-sm text-ink placeholder:text-muted md:w-44"
                    >
                </form>
            @endif

            @auth
                <a
                    href="{{ url('/dashboard') }}"
                    class="hidden min-h-11 items-center rounded-control px-2.5 text-sm font-semibold text-nav-muted hover:bg-white/10 hover:text-white sm:inline-flex"
                >Account</a>
                <button
                    type="button"
                    x-data
                    @click="$store.auth.logout()"
                    class="hidden h-11 w-11 items-center justify-center rounded-control text-nav-muted hover:bg-white/10 hover:text-white sm:inline-flex"
                    aria-label="Sign out"
                    title="Sign out"
                ><x-ui.icon name="logout" :size="18" /></button>
            @else
                <a
                    href="{{ route('login') }}"
                    class="hidden min-h-11 items-center rounded-control px-2.5 text-sm font-semibold text-nav-muted hover:bg-white/10 hover:text-white sm:inline-flex"
                >Sign in</a>
            @endauth

            @if ($cartUrl)
                <a
                    href="{{ $cartUrl }}"
                    class="inline-flex min-h-11 items-center gap-1.5 rounded-control bg-brand-yellow px-3 text-sm font-extrabold text-brand-navy hover:bg-brand-yellow-strong focus-visible:outline-brand-yellow"
                    aria-label="Cart, {{ $cartCount }} {{ \Illuminate\Support\Str::plural('item', $cartCount) }}"
                >
                    <x-ui.icon name="cart" :size="16" />
                    <span class="hidden sm:inline">Cart</span>
                    @if ($cartCount > 0)
                        <span class="inline-flex min-h-5 min-w-5 items-center justify-center rounded-control bg-brand-red-strong px-1 text-xs font-bold text-white" aria-hidden="true">
                            {{ $cartCount }}
                        </span>
                    @endif
                </a>
            @endif
        </div>
    </div>

    <div
        x-show="mobileNavOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[80] lg:hidden"
        id="mobile-public-nav"
        role="dialog"
        aria-modal="true"
        aria-label="Mobile navigation"
        @keydown.escape.window="mobileNavOpen = false"
    >
        <div class="absolute inset-0 bg-brand-navy/70" @click="mobileNavOpen = false" aria-hidden="true"></div>

        <aside class="absolute inset-y-0 left-0 flex w-72 max-w-[85vw] flex-col overflow-y-auto bg-brand-navy text-white">
            <div class="flex items-center justify-between gap-2 border-b border-white/15 px-4 py-3">
                <span class="text-sm font-extrabold tracking-[0.06em] text-white">{{ \Illuminate\Support\Str::upper($brand) }}</span>
                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-control text-nav-muted hover:bg-white/10 hover:text-white"
                    aria-label="Close navigation"
                    @click="mobileNavOpen = false"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <nav class="flex-1 px-3 py-3" aria-label="Public sections">
                <ul class="space-y-0.5">
                    @foreach ($navigation as $item)
                        <li>
                            <a
                                href="{{ $item['url'] }}"
                                @if (! empty($item['active'])) aria-current="page" @endif
                                @class([
                                    'flex min-h-11 items-center rounded-control px-2 text-sm font-semibold text-nav-muted hover:bg-white/10 hover:text-white',
                                    'bg-white/10 text-white' => ! empty($item['active']),
                                ])
                            >
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="border-t border-white/15 px-3 py-3">
                @auth
                    <a href="{{ url('/dashboard') }}" class="flex min-h-11 items-center rounded-control px-2 text-sm font-semibold text-nav-muted hover:bg-white/10 hover:text-white">Account</a>
                    <button type="button" x-data @click="$store.auth.logout()" class="flex min-h-11 w-full items-center justify-center rounded-control px-2 text-nav-muted hover:bg-white/10 hover:text-white" aria-label="Sign out" title="Sign out"><x-ui.icon name="logout" :size="20" /></button>
                @else
                    <a href="{{ route('login') }}" class="flex min-h-11 items-center rounded-control px-2 text-sm font-semibold text-nav-muted hover:bg-white/10 hover:text-white">Sign in</a>
                @endauth
                @if ($cartUrl)
                    <a href="{{ $cartUrl }}" class="mt-1 flex min-h-11 items-center justify-center rounded-control bg-brand-yellow px-3 text-sm font-extrabold text-brand-navy hover:bg-brand-yellow-strong">
                        Cart ({{ $cartCount }})
                    </a>
                @endif
            </div>
        </aside>
    </div>
</header>
