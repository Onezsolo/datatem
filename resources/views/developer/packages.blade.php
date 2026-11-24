<x-layouts.admin title="Template packages">
    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Developer</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">Template packages</h1>
                <p class="mt-1 text-sm text-muted">The business is the template package: configure this deployment, save it as a package, then apply it to client installations before handover.</p>
            </div>
            <a href="{{ route('developer.dashboard') }}" class="inline-flex min-h-11 items-center rounded-control border border-line bg-surface px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Developer console</a>
        </header>

        @if (session('status'))
            <div class="rounded-card border border-brand-yellow bg-surface p-4 text-sm font-semibold text-brand-navy" role="status">{{ session('status') }}</div>
        @endif

        @if ($applied)
            <div class="rounded-card border border-line bg-surface p-5">
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Applied on this deployment</p>
                <p class="mt-1 text-lg font-extrabold text-brand-navy">{{ $applied['name'] }} <span class="text-sm font-semibold text-muted">v{{ $applied['version'] }}</span></p>
                <p class="mt-1 text-xs text-muted">Applied {{ \Illuminate\Support\Carbon::parse($applied['applied_at'])->format('d M Y, H:i') }} by {{ $applied['applied_by'] }}</p>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[22rem_1fr]">
            <form action="{{ route('developer.packages.store') }}" method="POST" class="h-fit rounded-card border border-line bg-surface p-5">
                @csrf
                <h2 class="text-lg font-extrabold text-brand-navy">Save current setup as package</h2>
                <p class="mt-1 text-xs leading-relaxed text-muted">Captures the design preset, feature switches, service switches, AFA price and provider sources (credentials encrypted) from this deployment. Business data — products, orders and users — is not included.</p>

                <div class="mt-4 space-y-3">
                    <div>
                        <label for="name" class="block text-sm font-bold text-brand-navy">Package name</label>
                        <input id="name" name="name" type="text" required value="{{ old('name') }}" placeholder="Mall Starter" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                        @error('name')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="version" class="block text-sm font-bold text-brand-navy">Version</label>
                        <input id="version" name="version" type="text" required value="{{ old('version', '1.0.0') }}" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-bold text-brand-navy">Description</label>
                        <textarea id="description" name="description" rows="3" class="mt-1 w-full rounded-control border border-line px-3 py-2 text-sm">{{ old('description') }}</textarea>
                    </div>
                </div>

                <button type="submit" class="mt-4 min-h-10 w-full rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Save package</button>
            </form>

            <div class="space-y-3">
                @forelse ($packages as $package)
                    @php($summary = $package->summary())
                    <div class="rounded-card border border-line bg-surface p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-brand-navy">{{ $package->name }} <span class="text-xs font-semibold text-muted">v{{ $package->version }}</span></p>
                                @if ($package->description)
                                    <p class="mt-1 max-w-2xl text-sm text-muted">{{ $package->description }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <form action="{{ route('developer.packages.apply', $package) }}" method="POST" onsubmit="return confirm('Apply this package to the current deployment? Theme, features, services and provider sources will be replaced.')">
                                    @csrf
                                    <button type="submit" class="min-h-9 rounded-control bg-brand-navy px-3 text-sm font-bold text-white hover:bg-brand-navy-soft">Apply to deployment</button>
                                </form>
                                <form action="{{ route('developer.packages.destroy', $package) }}" method="POST" onsubmit="return confirm('Delete this package?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="min-h-9 rounded-control border border-brand-red-strong px-3 text-sm font-bold text-brand-red-strong hover:bg-surface-muted">Delete</button>
                                </form>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2 text-xs">
                            <span class="inline-flex min-h-6 items-center rounded-control bg-surface-muted px-2 font-bold text-brand-navy">Design: {{ \App\Services\ThemeSettings::presets()[$summary['theme']]['label'] ?? $summary['theme'] }}</span>
                            <span class="inline-flex min-h-6 items-center rounded-control bg-surface-muted px-2 font-bold text-brand-navy">Features: {{ $summary['features'] }}</span>
                            <span class="inline-flex min-h-6 items-center rounded-control bg-surface-muted px-2 font-bold text-brand-navy">Services: {{ $summary['services'] }}</span>
                            <span class="inline-flex min-h-6 items-center rounded-control bg-surface-muted px-2 font-bold text-brand-navy">Sources: {{ $summary['providers'] }}</span>
                        </div>

                        <p class="mt-2 text-xs text-muted">
                            Applied {{ $package->applied_count }} time(s)
                            @if ($package->last_applied_at)
                                · last {{ $package->last_applied_at->format('d M Y, H:i') }}@if ($package->last_applied_domain) on {{ $package->last_applied_domain }}@endif
                            @endif
                        </p>
                    </div>
                @empty
                    <div class="rounded-card border border-line bg-surface p-8 text-center">
                        <p class="text-sm font-bold text-brand-navy">No packages yet.</p>
                        <p class="mt-1 text-sm text-muted">Configure this deployment the way clients should receive it, then save it as your first package.</p>
                    </div>
                @endforelse

                {{ $packages->links() }}
            </div>
        </div>
    </div>
</x-layouts.admin>
