@props(['title' => null])

@php
    $groups = \App\Support\Navigation::member();
@endphp

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' - '.\App\Support\Brand::name() : \App\Support\Brand::name() }}</title>
    <link rel="icon" type="image/png" href="{{ \App\Support\Brand::faviconUrl() }}">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @include('partials.theme-style')
</head>
<body class="min-h-full bg-surface-muted pb-16 text-ink lg:pb-0">
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <div class="mx-auto flex min-h-screen w-full max-w-[90rem]" x-data="{ mobileSidebarOpen: false }">
        <x-shell.member-sidebar :groups="$groups" />
        <x-shell.member-mobile-drawer :groups="$groups" />

        <div class="flex min-w-0 flex-1 flex-col">
            <x-shell.member-topbar :title="$title" />

            <main id="main-content" class="container-page w-full flex-1 py-6">
                @if (session('status'))
                    <div class="mb-4 rounded-card border border-line bg-surface p-4 text-sm font-semibold text-brand-navy" role="status">{{ session('status') }}</div>
                @endif
                @if (session('warning'))
                    <div class="mb-4 rounded-card border border-brand-red-strong bg-surface p-4 text-sm text-brand-navy" role="alert">{{ session('warning') }}</div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <x-shell.bottom-nav />
    <x-shell.alert-modal />
</body>
</html>
