<x-layouts.admin :title="$variant['name']">
    @php
        $tiers = \App\Services\TemplateProductService::tiersFor($key);
        $currency = \App\Services\TemplateProductService::currency();
        $docsUrl = \App\Services\TemplateProductService::docsUrl();
        $features = \App\Services\FeatureSettings::definitions();
        $serviceTypes = \App\Services\Patupaa\PatupaaSettings::TYPES;
    @endphp

    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <a href="{{ route('developer.products') }}" class="text-xs font-bold uppercase tracking-wide text-muted">Template catalog</a>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-extrabold tracking-tight text-brand-navy">{{ $variant['name'] }}</h1>
                    <x-ui.badge variant="neutral" size="sm">{{ $variant['category'] ?? 'Template' }}</x-ui.badge>
                    <x-ui.badge variant="accent" size="sm">v{{ $variant['version'] ?? '1.0.0' }}</x-ui.badge>
                </div>
                <p class="mt-1 max-w-2xl text-sm text-muted">{{ $variant['description'] }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('developer.products.preview', $key) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center rounded-control border border-line bg-surface px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Live preview</a>
                @if ($docsUrl)
                    <a href="{{ $docsUrl }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center rounded-control border border-line bg-surface px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Documentation</a>
                @endif
                <a href="{{ route('developer.licenses', ['product' => $key]) }}" class="inline-flex min-h-11 items-center rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Issue license</a>
            </div>
        </header>

        @if (session('status'))
            <div class="rounded-card border border-brand-yellow bg-surface p-4 text-sm font-semibold text-brand-navy" role="status">{{ session('status') }}</div>
        @endif

        @if (! empty($variant['highlights']))
            <ul class="grid gap-2 sm:grid-cols-3">
                @foreach ($variant['highlights'] as $highlight)
                    <li class="flex items-start gap-2 rounded-card border border-line bg-surface p-4 text-sm text-brand-navy">
                        <x-ui.icon name="check" :size="16" class="mt-0.5 shrink-0 text-brand-navy" />
                        {{ $highlight }}
                    </li>
                @endforeach
            </ul>
        @endif

        <section aria-labelledby="pricing-heading">
            <h2 id="pricing-heading" class="text-xl font-extrabold text-brand-navy">License pricing</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach ($tiers as $tierKey => $tier)
                    <div class="rounded-card border border-line bg-surface p-5 {{ $tierKey === 'extended' ? 'border-brand-yellow' : '' }}">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-bold text-brand-navy">{{ $tier['label'] }}</p>
                                <p class="mt-0.5 text-xs text-muted">{{ $tierKey === 'standard' ? 'One client deployment' : 'Agency / resale rights' }}</p>
                            </div>
                            <x-ui.badge :variant="$tierKey === 'extended' ? 'accent' : 'neutral'" size="sm">{{ $tier['support_days'] }} days support</x-ui.badge>
                        </div>
                        <p class="mt-3 text-2xl font-extrabold text-brand-navy">{{ $currency }} {{ number_format((float) $tier['price'], 2) }}</p>
                        <ul class="mt-3 space-y-1 text-xs text-muted">
                            <li>{{ $tier['deployments'] === null ? 'Unlimited deployments under agreement' : $tier['deployments'].' deployment' }}</li>
                            <li>{{ $tier['resale_allowed'] ? 'Resale / client billing allowed' : 'Single end product, no resale' }}</li>
                            <li>Updates included while support is active</li>
                        </ul>
                        <a href="{{ route('developer.licenses', ['product' => $key, 'tier' => $tierKey]) }}" class="mt-4 inline-flex min-h-10 w-full items-center justify-center rounded-control bg-brand-navy px-4 text-sm font-bold text-white hover:bg-brand-navy-soft">Issue {{ $tier['label'] }}</a>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
            <section class="rounded-card border border-line bg-surface p-5">
                <h2 class="text-lg font-extrabold text-brand-navy">Feature matrix</h2>
                <table class="mt-3 w-full text-left text-sm">
                    <tbody class="divide-y divide-line">
                        @foreach ($features as $featureKey => $label)
                            <tr>
                                <td class="py-2 text-brand-navy">{{ $label }}</td>
                                <td class="py-2 text-right">
                                    @if (! empty($variant['features'][$featureKey]))
                                        <span class="inline-flex min-h-6 items-center rounded-control bg-brand-yellow px-2 text-xs font-bold text-brand-navy">Included</span>
                                    @else
                                        <span class="text-xs text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        <tr>
                            <td class="py-2 text-brand-navy">Provider services</td>
                            <td class="py-2 text-right text-xs text-muted">
                                @foreach ($serviceTypes as $serviceKey => $label)
                                    @if (! empty($variant['services'][$serviceKey]))
                                        {{ $label }},
                                    @endif
                                @endforeach
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 text-brand-navy">Design preset</td>
                            <td class="py-2 text-right text-xs text-brand-navy">{{ \App\Services\ThemeSettings::presets()[$variant['theme']]['label'] ?? $variant['theme'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="rounded-card border border-line bg-surface p-5">
                <h2 class="text-lg font-extrabold text-brand-navy">What's included</h2>
                <ul class="mt-3 space-y-2 text-sm text-muted">
                    @foreach (\App\Services\TemplateProductService::includes() as $item)
                        <li class="flex items-start gap-2">
                            <x-ui.icon name="check" :size="14" class="mt-0.5 shrink-0 text-brand-navy" />
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 rounded-control border border-line bg-surface-muted p-3 text-xs text-muted">
                    Support: <span class="font-bold text-brand-navy">{{ $tiers['standard']['support_days'] ?? 180 }} days standard / {{ $tiers['extended']['support_days'] ?? 365 }} days extended</span>.
                    Updates are included while support is active.
                </div>
            </section>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
            <section class="rounded-card border border-line bg-surface p-5">
                <h2 class="text-lg font-extrabold text-brand-navy">Changelog</h2>
                <ol class="mt-3 space-y-3">
                    @foreach (($variant['changelog'] ?? []) as $release)
                        <li class="border-l-2 border-brand-yellow pl-3">
                            <p class="text-sm font-bold text-brand-navy">v{{ $release['version'] }} <span class="font-normal text-muted">· {{ $release['date'] }}</span></p>
                            <p class="mt-0.5 text-sm text-muted">{{ $release['notes'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>

            <section class="rounded-card border border-line bg-surface p-5">
                <h2 class="text-lg font-extrabold text-brand-navy">Template paths</h2>
                <p class="mt-1 text-xs text-muted">Individual paths; opening them never changes this deployment.</p>
                <dl class="mt-3 space-y-2 text-xs">
                    <div><dt class="text-muted">Console path</dt><dd class="break-all font-mono text-brand-navy">{{ url('/developer/products/'.$key) }}</dd></div>
                    <div><dt class="text-muted">Preview path</dt><dd class="break-all font-mono text-brand-navy">{{ url('/developer/products/'.$key.'/preview') }}</dd></div>
                </dl>
                <form action="{{ route('developer.products.package', $key) }}" method="POST" class="mt-4" onsubmit="return confirm('Create a client package from this template? This does not change the current deployment.')">
                    @csrf
                    <button type="submit" class="min-h-10 w-full rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Create client package</button>
                </form>
            </section>
        </div>
    </div>
</x-layouts.admin>
