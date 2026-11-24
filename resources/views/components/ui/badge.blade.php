@props([
    'variant' => 'neutral',
    'size' => 'md',
])

@php
    $variants = [
        'neutral' => 'border border-line bg-surface-muted text-brand-navy',
        'accent' => 'bg-brand-yellow text-brand-navy',
        'danger' => 'bg-brand-red-strong text-white',
        'info' => 'bg-brand-navy text-white',
    ];

    $sizes = [
        'sm' => 'min-h-6 px-2 text-[0.6875rem]',
        'md' => 'min-h-7 px-2.5 text-xs',
    ];

    $classes = 'inline-flex items-center gap-1 rounded-control font-bold leading-none '
        .($variants[$variant] ?? $variants['neutral']).' '
        .($sizes[$size] ?? $sizes['md']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</span>
