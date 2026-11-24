@props([
    'id' => null,
    'label' => 'Items',
    'heading' => null,
    'description' => null,
])

@php
    $id = $id ?? 'carousel-'.\Illuminate\Support\Str::random(8);
@endphp

<section {{ $attributes->merge(['class' => 'min-w-0']) }} aria-labelledby="{{ $id }}-heading" data-carousel>
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 id="{{ $id }}-heading" class="text-xl font-extrabold tracking-tight text-brand-navy">
                {{ $heading ?? $label }}
            </h2>
            @if ($description)
                <p class="mt-1 text-sm text-muted">{{ $description }}</p>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <button
                type="button"
                data-carousel-prev
                aria-controls="{{ $id }}"
                aria-label="Scroll {{ $label }} backward"
                class="inline-flex h-11 w-11 items-center justify-center rounded-control border border-line bg-surface text-brand-navy hover:bg-surface-muted disabled:opacity-40"
            >
                <x-ui.icon name="chevron-left" :size="18" />
            </button>
            <button
                type="button"
                data-carousel-next
                aria-controls="{{ $id }}"
                aria-label="Scroll {{ $label }} forward"
                class="inline-flex h-11 w-11 items-center justify-center rounded-control border border-line bg-surface text-brand-navy hover:bg-surface-muted disabled:opacity-40"
            >
                <x-ui.icon name="chevron-right" :size="18" />
            </button>
        </div>
    </div>

    <ul id="{{ $id }}" data-carousel-track class="carousel-track flex gap-3 overflow-x-auto pb-2" tabindex="0" aria-label="{{ $label }}">
        {{ $slot }}
    </ul>
</section>
