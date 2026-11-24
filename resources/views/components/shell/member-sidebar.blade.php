@props(['groups' => []])

<aside class="hidden w-64 shrink-0 bg-brand-navy text-white lg:block" aria-label="Member navigation">
    <div class="sticky top-0 flex h-screen flex-col overflow-y-auto">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5 border-b border-white/15 px-5 py-4 focus-visible:outline-brand-yellow" aria-label="{{ \App\Support\Brand::name() }} home">
            <img src="{{ \App\Support\Brand::logoUrl() }}" alt="" width="48" height="48" class="h-10 w-10 rounded-control bg-surface object-contain p-1">
            <span class="leading-none">
                <span class="block text-sm font-extrabold tracking-[0.06em] text-white">{{ \Illuminate\Support\Str::upper(\App\Support\Brand::name()) }}</span>
                <span class="mt-1 block text-[0.5rem] font-bold tracking-[0.24em] text-brand-yellow">DATA &amp; SHOPPING</span>
            </span>
        </a>

        <nav class="flex-1 px-3 py-4">
            @foreach ($groups as $group => $items)
                <p class="mt-4 px-2 text-[0.625rem] font-bold uppercase tracking-[0.14em] text-nav-muted first:mt-0">{{ $group }}</p>
                <ul class="mt-1 space-y-0.5">
                    @foreach ($items as $item)
                        <li>
                            <a
                                href="{{ $item['url'] }}"
                                @if ($item['active']) aria-current="page" @endif
                                @class([
                                    'flex min-h-10 items-center gap-2.5 rounded-control px-2 text-sm font-semibold',
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
    </div>
</aside>
