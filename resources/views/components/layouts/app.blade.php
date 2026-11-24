@props([
    'title' => null,
    'description' => null,
])

<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' - '.\App\Support\Brand::name() : \App\Support\Brand::name() }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    <link rel="icon" type="image/png" href="{{ \App\Support\Brand::faviconUrl() }}">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-full bg-surface text-ink">
    <a class="skip-link" href="#main-content">Skip to main content</a>

    {{ $slot }}
</body>
</html>
