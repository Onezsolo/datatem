@props([
    'title' => null,
    'description' => null,
])

@if (auth()->check())
    <x-layouts.member :title="$title">
        {{ $slot }}
    </x-layouts.member>
@else
    <x-layouts.public :title="$title" :description="$description">
        {{ $slot }}
    </x-layouts.public>
@endif
