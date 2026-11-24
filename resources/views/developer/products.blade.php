<x-layouts.admin title="Template products">
    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Developer</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">Template products</h1>
                <p class="mt-1 text-sm text-muted">Five ready product lines, each with its own path and preview. Opening a template never changes the current deployment; create a package to set up a client.</p>
            </div>
            <a href="{{ route('developer.dashboard') }}" class="inline-flex min-h-11 items-center rounded-control border border-line bg-surface px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Developer console</a>
        </header>

        @if (session('status'))
            <div class="rounded-card border border-brand-yellow bg-surface p-4 text-sm font-semibold text-brand-navy" role="status">{{ session('status') }}</div>
        @endif

        <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
            @foreach ($variants as $key => $variant)
                <div class="flex h-full flex-col rounded-card border border-line bg-surface p-5">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-lg font-extrabold text-brand-navy">{{ $variant['name'] }}</p>
                        <x-ui.badge variant="neutral" size="sm">{{ $variant['category'] ?? 'Template' }}</x-ui.badge>
                    </div>
                    <p class="mt-1 text-sm text-muted">{{ $variant['description'] }}</p>
                    <p class="mt-2 text-xs font-bold text-brand-navy">From {{ \App\Services\TemplateProductService::currency() }} {{ number_format((float) (\App\Services\TemplateProductService::tiersFor($key)['standard']['price'] ?? 0), 2) }} <span class="font-normal text-muted">· v{{ $variant['version'] ?? '1.0.0' }}</span></p>

                    <dl class="mt-4 space-y-2 text-xs">
                        <div>
                            <dt class="font-bold uppercase tracking-wide text-muted">Modules</dt>
                            <dd class="mt-0.5 text-brand-navy">{{ \App\Services\TemplateProductService::featureSummary($variant) ?: 'None' }}</dd>
                        </div>
                        <div>
                            <dt class="font-bold uppercase tracking-wide text-muted">Provider services</dt>
                            <dd class="mt-0.5 text-brand-navy">{{ \App\Services\TemplateProductService::serviceSummary($variant) }}</dd>
                        </div>
                        <div>
                            <dt class="font-bold uppercase tracking-wide text-muted">Design</dt>
                            <dd class="mt-0.5 text-brand-navy">{{ \App\Services\ThemeSettings::presets()[$variant['theme']]['label'] ?? $variant['theme'] }}</dd>
                        </div>
                    </dl>

                    <div class="mt-auto flex gap-2 pt-4">
                        <a href="{{ route('developer.products.show', $key) }}" class="inline-flex min-h-10 flex-1 items-center justify-center rounded-control bg-brand-yellow px-3 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Open template</a>
                        <a href="{{ route('developer.products.preview', $key) }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center justify-center rounded-control border border-line bg-surface px-3 text-sm font-bold text-brand-navy hover:bg-surface-muted">Preview</a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.admin>
