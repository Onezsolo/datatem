@props([
    'title',
    'subtitle' => null,
    'backUrl' => null,
    'backLabel' => 'All services',
])

<div>
    @if ($backUrl)
        <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-brand-navy">
            <x-ui.icon name="chevron-left" :size="16" />
            {{ $backLabel }}
        </a>
    @endif

    <div class="{{ $backUrl ? 'mt-3' : '' }} flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-brand-navy">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1 text-sm text-muted">{{ $subtitle }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="flex items-center gap-3 text-sm">{{ $actions }}</div>
        @endisset
    </div>
</div>
