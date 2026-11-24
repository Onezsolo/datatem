@props(['groups' => []])

<div
    x-show="mobileSidebarOpen"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-[80] lg:hidden"
    role="dialog"
    aria-modal="true"
    aria-label="Mobile member navigation"
    id="mobile-member-nav"
    @keydown.escape.window="mobileSidebarOpen = false"
>
    <div class="absolute inset-0 bg-brand-navy/60" @click="mobileSidebarOpen = false" aria-hidden="true"></div>

    <aside class="absolute inset-y-0 left-0 flex w-72 max-w-[85vw] flex-col overflow-y-auto bg-brand-navy text-white">
        <div class="flex items-center justify-between gap-2 border-b border-white/15 px-4 py-3">
            <a href="{{ url('/dashboard') }}" class="flex items-center gap-2.5" aria-label="{{ \App\Support\Brand::name() }} home">
                <img src="{{ \App\Support\Brand::logoUrl() }}" alt="" width="40" height="40" class="h-9 w-9 rounded-control bg-surface object-contain p-1">
                <span class="leading-none">
                    <span class="block text-sm font-extrabold tracking-[0.06em] text-white">{{ \Illuminate\Support\Str::upper(\App\Support\Brand::name()) }}</span>
                    <span class="mt-1 block text-[0.5rem] font-bold tracking-[0.24em] text-brand-yellow">DATA &amp; SHOPPING</span>
                </span>
            </a>
            <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-control text-nav-muted hover:bg-white/10 hover:text-white"
                aria-label="Close navigation"
                @click="mobileSidebarOpen = false"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 px-3 py-4" aria-label="Member sections">
            @foreach ($groups as $group => $items)
                <p class="mt-4 px-2 text-[0.625rem] font-bold uppercase tracking-[0.14em] text-nav-muted first:mt-0">{{ $group }}</p>
                <ul class="mt-1 space-y-0.5">
                    @foreach ($items as $item)
                        <li>
                            <a
                                href="{{ $item['url'] }}"
                                @if ($item['active']) aria-current="page" @endif
                                @class([
                                    'flex min-h-11 items-center gap-2.5 rounded-control px-2 text-sm font-semibold',
                                    'bg-brand-yellow text-brand-navy' => $item['active'],
                                    'text-nav-muted hover:bg-white/10 hover:text-white' => ! $item['active'],
                                ])
                            >
                                @if ($item['icon'])
                                    <x-ui.icon :name="$item['icon']" :size="18" />
                                @endif
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>

        <div class="border-t border-white/15 px-3 py-4">
            <a href="{{ route('home') }}" class="flex min-h-10 items-center gap-2.5 rounded-control px-2 text-sm font-semibold text-nav-muted hover:bg-white/10 hover:text-white">
                <x-ui.icon name="grid" :size="18" />
                View store
            </a>
        </div>
    </aside>
</div>
