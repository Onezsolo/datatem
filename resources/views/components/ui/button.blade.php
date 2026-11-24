@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'disabled' => false,
])

@php
    $variants = [
        'primary' => 'bg-brand-yellow text-brand-navy hover:bg-brand-yellow-strong focus-visible:outline-brand-navy',
        'secondary' => 'bg-brand-navy text-white hover:bg-brand-navy-soft focus-visible:outline-brand-yellow',
        'danger' => 'bg-brand-red-strong text-white hover:bg-brand-red-strong-hover focus-visible:outline-brand-navy',
        'outline' => 'border border-brand-navy bg-surface text-brand-navy hover:bg-surface-muted focus-visible:outline-brand-navy',
        'ghost' => 'border border-white/50 bg-transparent text-white hover:bg-white/10 focus-visible:outline-brand-yellow',
        'link' => 'text-brand-navy underline underline-offset-4 hover:text-brand-red-strong focus-visible:outline-brand-navy',
    ];

    $sizes = [
        'sm' => 'min-h-11 px-3 text-sm',
        'md' => 'min-h-11 px-4 text-sm',
        'lg' => 'min-h-12 px-6 text-base',
    ];

    $classes = 'inline-flex items-center justify-center gap-2 rounded-control font-bold leading-none transition-colors disabled:pointer-events-none disabled:opacity-60 '
        .($variants[$variant] ?? $variants['primary']).' '
        .($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a
        href="{{ $href }}"
        @if ($disabled) aria-disabled="true" tabindex="-1" @endif
        {{ $attributes->merge(['class' => $classes.($disabled ? ' pointer-events-none opacity-60' : '')]) }}
    >
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
