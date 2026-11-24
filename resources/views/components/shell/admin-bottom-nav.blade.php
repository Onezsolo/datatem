@php
    $items = \App\Support\Navigation::adminBottom();
@endphp

<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface lg:hidden" aria-label="Admin bottom navigation">
    <ul class="grid grid-cols-4">
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
                    <x-ui.icon :name="$item['icon']" :size="20" />
                    {{ $item['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
