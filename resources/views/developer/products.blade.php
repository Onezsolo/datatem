<x-layouts.admin title="Template products">
    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Developer</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">Template products</h1>
                <p class="mt-1 text-sm text-muted">Five ready product lines. Apply one to configure this deployment's modules, services and design exactly as that template portrays.</p>
            </div>
            <a href="{{ route('developer.dashboard') }}" class="inline-flex min-h-11 items-center rounded-control border border-line bg-surface px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Developer console</a>
        </header>

        @if (session('status'))
            <div class="rounded-card border border-brand-yellow bg-surface p-4 text-sm font-semibold text-brand-navy" role="status">{{ session('status') }}</div>
        @endif

        <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
            @foreach ($variants as $key => $variant)
                @php($isActive = ($active['key'] ?? null) === $key)
                <div class="flex h-full flex-col rounded-card border p-5 {{ $isActive ? 'border-brand-navy bg-surface' : 'border-line bg-surface' }}">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="text-lg font-extrabold text-brand-navy">{{ $variant['name'] }}</p>
                            <p class="mt-1 text-sm text-muted">{{ $variant['description'] }}</p>
                        </div>
                        @if ($isActive)
                            <span class="inline-flex min-h-6 items-center rounded-control bg-brand-yellow px-2 text-xs font-bold text-brand-navy">Active</span>
                        @endif
                    </div>

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

                    <form action="{{ route('developer.products.apply', $key) }}" method="POST" class="mt-auto pt-4" onsubmit="return confirm('Apply the {{ $variant['name'] }} template to this deployment? Modules, services and design will be replaced.')">
                        @csrf
                        <button type="submit" class="min-h-10 w-full rounded-control px-4 text-sm font-bold {{ $isActive ? 'border border-line bg-surface text-brand-navy hover:bg-surface-muted' : 'bg-brand-yellow text-brand-navy hover:bg-brand-yellow-strong' }}">
                            {{ $isActive ? 'Re-apply this template' : 'Set up this template' }}
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.admin>
