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

<header class="sticky top-0 z-50 border-b-[3px] border-brand-yellow bg-brand-navy text-white" role="banner">
    <div class="container-page flex flex-wrap items-center gap-x-3 gap-y-2 py-2.5 lg:flex-nowrap">
        <a href="{{ $homeUrl }}" class="flex shrink-0 items-center gap-2.5 rounded-control focus-visible:outline-brand-yellow" aria-label="{{ $brand }} home">
            <img src="{{ \App\Support\Brand::logoUrl() }}" alt="" width="48" height="48" class="h-9 w-9 rounded-control bg-surface object-contain p-1 sm:h-10 sm:w-10">
            <span class="leading-none">
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
                            @if (! empty($item['current'])) aria-current="page" @endif
                            @class([
                                'inline-flex min-h-11 items-center whitespace-nowrap rounded-control px-2.5 text-sm font-semibold text-nav-muted hover:bg-white/10 hover:text-white focus-visible:outline-brand-yellow',
                                'bg-white/10 text-white' => ! empty($item['current']),
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
</header>
