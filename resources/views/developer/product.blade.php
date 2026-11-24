<x-layouts.admin :title="$variant['name']">
    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <a href="{{ route('developer.products') }}" class="text-xs font-bold uppercase tracking-wide text-muted">Template products</a>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">{{ $variant['name'] }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-muted">{{ $variant['description'] }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('developer.products.preview', $key) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center rounded-control border border-line bg-surface px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Open preview</a>
                <form action="{{ route('developer.products.package', $key) }}" method="POST" onsubmit="return confirm('Create a client package from this template? This does not change the current deployment.')">
                    @csrf
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Create client package</button>
                </form>
            </div>
        </header>

        @if (session('status'))
            <div class="rounded-card border border-brand-yellow bg-surface p-4 text-sm font-semibold text-brand-navy" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-card border border-brand-red-strong bg-surface p-4 text-sm text-brand-navy" role="alert">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-card border border-line bg-surface p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Modules enabled</p>
                <p class="mt-2 text-sm leading-relaxed text-brand-navy">{{ \App\Services\TemplateProductService::featureSummary($variant) ?: 'None' }}</p>
            </div>
            <div class="rounded-card border border-line bg-surface p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Provider services</p>
                <p class="mt-2 text-sm leading-relaxed text-brand-navy">{{ \App\Services\TemplateProductService::serviceSummary($variant) }}</p>
            </div>
            <div class="rounded-card border border-line bg-surface p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Design preset</p>
                <p class="mt-2 text-sm leading-relaxed text-brand-navy">{{ \App\Services\ThemeSettings::presets()[$variant['theme']]['label'] ?? $variant['theme'] }}</p>
            </div>
        </div>

        <section class="rounded-card border border-line bg-surface p-5">
            <h2 class="text-lg font-extrabold text-brand-navy">Template path</h2>
            <p class="mt-1 text-sm text-muted">Each product line has its own path here. Opening or previewing it never changes this deployment.</p>
            <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                <div><dt class="text-muted">Console path</dt><dd class="break-all font-mono font-bold text-brand-navy">{{ url('/developer/products/'.$key) }}</dd></div>
                <div><dt class="text-muted">Preview path</dt><dd class="break-all font-mono font-bold text-brand-navy">{{ url('/developer/products/'.$key.'/preview') }}</dd></div>
            </dl>

            <div class="mt-5 rounded-control border border-line bg-surface-muted p-4 text-sm text-muted">
                <p class="font-bold text-brand-navy">Setup flow for a client</p>
                <ol class="mt-2 list-inside list-decimal space-y-1">
                    <li>Create a client package from this template.</li>
                    <li>Apply the package on the client deployment from Template packages.</li>
                    <li>Set the deployment license and complete handover.</li>
                </ol>
            </div>
        </section>
    </div>
</x-layouts.admin>
