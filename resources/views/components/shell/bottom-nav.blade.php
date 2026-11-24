@props(['cartCount' => 0])

@php
    $items = \App\Support\Navigation::bottom();
@endphp

<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface lg:hidden" aria-label="Bottom navigation">
    <ul class="grid" style="grid-template-columns: repeat({{ max(count($items), 1) }}, minmax(0, 1fr));">
        @foreach ($items as $item)
            <li>
                <a
                    href="{{ $item['url'] }}"
                    @if ($item['active']) aria-current="page" @endif
                    @class([
                        'flex min-h-14 flex-col items-center justify-center gap-1 px-1 py-2 text-[0.6875rem] font-bold',
                        'text-brand-navy' => $item['active'],
                        'text-muted hover:text-brand-navy' => ! $item['active'],
                    ])
                >
                    <span class="relative">
                        <x-ui.icon :name="$item['icon']" :size="20" />
                        @if ($item['label'] === 'Shop' && $cartCount > 0)
                            <span class="absolute -right-2 -top-2 inline-flex h-4 min-w-4 items-center justify-center rounded-control bg-brand-red-strong px-1 text-[0.625rem] font-bold text-white">{{ $cartCount }}</span>
                        @endif
                    </span>
                    {{ $item['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
