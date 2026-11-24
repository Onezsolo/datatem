<!DOCTYPE html>
<html lang="en" class="h-full" style="
    --color-brand-navy: {{ $colours['brand-navy'] ?? '#0A2038' }};
    --color-brand-navy-soft: {{ $colours['brand-navy-soft'] ?? '#143A5A' }};
    --color-brand-yellow: {{ $colours['brand-yellow'] ?? '#F0C232' }};
    --color-brand-yellow-strong: {{ $colours['brand-yellow-strong'] ?? '#D4A81F' }};
    --color-brand-red: {{ $colours['brand-red'] ?? '#DB4650' }};
    --color-brand-red-strong: {{ $colours['brand-red-strong'] ?? '#A92D38' }};
    --color-brand-red-strong-hover: {{ $colours['brand-red-strong-hover'] ?? '#8C232D' }};
    --color-surface: {{ $colours['surface'] ?? '#FCFBF8' }};
    --color-surface-muted: {{ $colours['surface-muted'] ?? '#F2F0EB' }};
    --color-ink: {{ $colours['ink'] ?? '#14171A' }};
    --color-muted: {{ $colours['muted'] ?? '#5C6472' }};
    --color-line: {{ $colours['line'] ?? '#E1DFD8' }};
    --color-nav-muted: {{ $colours['nav-muted'] ?? '#D3DAE6' }};
">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preview - {{ $variant['name'] }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif
</head>
<body class="min-h-full bg-surface text-ink">
    <div class="border-b border-brand-yellow bg-brand-navy px-4 py-2 text-center text-xs font-bold uppercase tracking-[0.2em] text-brand-yellow">
        Template preview - {{ $variant['name'] }} - not applied to this deployment
    </div>

    <header class="bg-brand-navy text-white">
        <div class="container-page flex flex-wrap items-center justify-between gap-3 py-3">
            <span class="flex items-center gap-2.5">
                <img src="{{ \App\Support\Brand::logoUrl() }}" alt="" class="h-9 w-9 rounded-control bg-surface object-contain p-1">
                <span class="leading-none">
                    <span class="block text-sm font-extrabold tracking-[0.06em]">{{ \Illuminate\Support\Str::upper(\App\Support\Brand::name()) }}</span>
                    <span class="mt-1 block text-[0.625rem] font-bold tracking-[0.24em] text-brand-yellow">{{ \Illuminate\Support\Str::upper($variant['name']) }}</span>
                </span>
            </span>
            <nav class="flex flex-wrap gap-1 text-xs font-bold" aria-label="Template modules">
                <span class="rounded-control bg-brand-yellow px-2.5 py-1.5 text-brand-navy">Home</span>
                @foreach (array_keys(array_filter($variant['features'] ?? [])) as $module)
                    <span class="rounded-control px-2.5 py-1.5 text-nav-muted">{{ ucfirst(str_replace('_', ' ', $module)) }}</span>
                @endforeach
            </nav>
        </div>
    </header>

    <main class="container-page py-10">
        <div class="grid gap-8 lg:grid-cols-[1.15fr_1fr] lg:items-center">
            <div>
                <p class="inline-flex items-center gap-2 rounded-control border border-line px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-muted">
                    {{ $variant['name'] }} template
                </p>
                <h1 class="mt-4 text-4xl font-extrabold leading-tight tracking-tight text-brand-navy">{{ $variant['description'] }}</h1>
                <p class="mt-3 max-w-xl text-sm leading-relaxed text-muted">
                    This preview shows the admin setup and design direction for this product line. Use its path to review the configuration, then create a client package.
                </p>
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach (\App\Services\TemplateProductService::serviceLabels($variant) as $service)
                        <x-ui.badge variant="accent">{{ $service }}</x-ui.badge>
                    @endforeach
                    <x-ui.badge variant="neutral">Design: {{ \App\Services\ThemeSettings::presets()[$variant['theme']]['label'] ?? $variant['theme'] }}</x-ui.badge>
                </div>
            </div>

            <div class="rounded-card border border-brand-yellow bg-brand-navy-soft p-6 text-white">
                <h2 class="text-sm font-bold uppercase tracking-wide text-brand-yellow">Enabled modules</h2>
                <ul class="mt-3 grid grid-cols-2 gap-2 text-sm text-nav-muted">
                    @foreach (array_keys(array_filter($variant['features'] ?? [])) as $module)
                        <li class="flex items-center gap-2">
                            <x-ui.icon name="check" :size="14" class="text-brand-yellow" />
                            {{ ucfirst(str_replace('_', ' ', $module)) }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <section class="mt-10">
            <h2 class="text-xl font-extrabold text-brand-navy">What this template sets up</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-card border border-line bg-surface p-5">
                    <p class="text-xs font-bold uppercase tracking-wide text-muted">Modules</p>
                    <p class="mt-1 text-sm text-brand-navy">{{ \App\Services\TemplateProductService::featureSummary($variant) }}</p>
                </div>
                <div class="rounded-card border border-line bg-surface p-5">
                    <p class="text-xs font-bold uppercase tracking-wide text-muted">Services</p>
                    <p class="mt-1 text-sm text-brand-navy">{{ \App\Services\TemplateProductService::serviceSummary($variant) }}</p>
                </div>
                <div class="rounded-card border border-line bg-surface p-5">
                    <p class="text-xs font-bold uppercase tracking-wide text-muted">Design</p>
                    <p class="mt-1 text-sm text-brand-navy">{{ \App\Services\ThemeSettings::presets()[$variant['theme']]['label'] ?? $variant['theme'] }}</p>
                </div>
            </div>
        </section>

        <p class="mt-8 text-xs text-muted">Developer preview only - this page does not change the live deployment.</p>
    </main>
</body>
</html>
