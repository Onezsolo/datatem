@props([
    'variant' => 'default',
    'padding' => 'md',
    'href' => null,
])

@php
    $variants = [
        'default' => 'border border-line bg-surface',
        'muted' => 'bg-surface-muted',
        'product' => 'border border-line bg-surface overflow-hidden',
        'feature' => 'border border-brand-yellow bg-brand-navy text-white',
    ];

    $paddings = [
        'none' => '',
        'sm' => 'p-4',
        'md' => 'p-5',
        'lg' => 'p-6',
    ];

    $classes = 'block rounded-card '
        .($variants[$variant] ?? $variants['default']).' '
        .($paddings[$padding] ?? $paddings['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @isset($media)
            <div class="overflow-hidden">{{ $media }}</div>
        @endisset
        <div class="{{ $paddings[$padding] ?? $paddings['md'] }}">
            {{ $slot }}
            @isset($footer)
                <div class="mt-4">{{ $footer }}</div>
            @endisset
        </div>
    </a>
@else
    <article {{ $attributes->merge(['class' => $classes]) }}>
        @isset($media)
            <div class="overflow-hidden">{{ $media }}</div>
        @endisset
        <div class="{{ $paddings[$padding] ?? $paddings['md'] }}">
            {{ $slot }}
            @isset($footer)
                <div class="mt-4">{{ $footer }}</div>
            @endisset
        </div>
    </article>
@endif
