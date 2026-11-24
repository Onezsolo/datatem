<x-layouts.admin title="Developer console">
    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Developer</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">Developer console</h1>
                <p class="mt-1 text-sm text-muted">Template licensing, deployment status and quick access to the admin configuration.</p>
            </div>
            <a href="{{ route('developer.licenses') }}" class="inline-flex min-h-11 items-center rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Manage licenses</a>
        </header>

        @if (session('status'))
            <div class="rounded-card border border-brand-yellow bg-surface p-4 text-sm font-semibold text-brand-navy" role="status">{{ session('status') }}</div>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-card border border-line bg-surface p-5">
                <p class="text-[0.625rem] font-bold uppercase tracking-wider text-muted">Active licenses</p>
                <p class="mt-1 text-2xl font-extrabold text-brand-navy">{{ $licensesActive }}</p>
                <p class="mt-1 text-xs text-muted">{{ $licensesTotal }} issued · {{ $licensesRevoked }} revoked</p>
            </div>
            <div class="rounded-card border border-line bg-surface p-5">
                <p class="text-[0.625rem] font-bold uppercase tracking-wider text-muted">Admin accounts</p>
                <p class="mt-1 text-2xl font-extrabold text-brand-navy">{{ $admins }}</p>
                <p class="mt-1 text-xs text-muted">Admins and developers</p>
            </div>
            <div class="rounded-card border border-line bg-surface p-5">
                <p class="text-[0.625rem] font-bold uppercase tracking-wider text-muted">Modules enabled</p>
                <p class="mt-1 text-2xl font-extrabold text-brand-navy">{{ $featuresEnabled }} / {{ $featuresTotal }}</p>
                <p class="mt-1 text-xs text-muted">Feature switches</p>
            </div>
            <div class="rounded-card border border-line bg-surface p-5">
                <p class="text-[0.625rem] font-bold uppercase tracking-wider text-muted">Deployment license</p>
                <p class="mt-1 text-lg font-extrabold capitalize text-brand-navy">{{ $deployment['state'] }}</p>
                <p class="mt-1 text-xs text-muted">{{ $deployment['prefix'] ?: 'No key set' }}</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
            <section class="rounded-card border border-line bg-surface p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-extrabold text-brand-navy">Deployment license</h2>
                        <p class="mt-1 text-sm text-muted">Set the license issued for this installation. Keep the license key private.</p>
                    </div>
                </div>

                @if ($deployment['license'])
                    <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                        <div><dt class="text-muted">Client</dt><dd class="font-bold text-brand-navy">{{ $deployment['license']->client_name }}</dd></div>
                        <div><dt class="text-muted">Status</dt><dd class="font-bold text-brand-navy">{{ $deployment['license']->statusLabel() }}</dd></div>
                        <div><dt class="text-muted">Domain</dt><dd class="font-bold text-brand-navy">{{ $deployment['license']->domain ?: 'Any domain' }}</dd></div>
                        <div><dt class="text-muted">Expires</dt><dd class="font-bold text-brand-navy">{{ $deployment['license']->expires_at?->format('d M Y') ?: 'No expiry' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-muted">Agreement</dt><dd class="font-bold text-brand-navy">{{ $deployment['license']->agreement_reference ?: '—' }}</dd></div>
                    </dl>
                @endif

                <form action="{{ route('developer.deployment-license') }}" method="POST" class="mt-5">
                    @csrf
                    <label for="license_key" class="block text-sm font-bold text-brand-navy">License key</label>
                    <input id="license_key" name="license_key" type="text" required placeholder="DTMP-XXXX-XXXX-XXXX-XXXX" class="mt-1 h-11 w-full rounded-control border border-line px-3 font-mono text-sm">
                    @error('license_key')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                    <button type="submit" class="mt-3 min-h-10 rounded-control bg-brand-navy px-4 text-sm font-bold text-white hover:bg-brand-navy-soft">Set deployment license</button>
                </form>
            </section>

            <aside class="space-y-3">
                <a href="{{ route('admin.settings') }}" class="block rounded-card border border-line bg-surface p-5 hover:bg-surface-muted">
                    <p class="font-bold text-brand-navy">Template settings</p>
                    <p class="mt-1 text-sm text-muted">Features, five design presets, branding, payments and integrations.</p>
                </a>
                <a href="{{ route('admin.services') }}" class="block rounded-card border border-line bg-surface p-5 hover:bg-surface-muted">
                    <p class="font-bold text-brand-navy">Service sources</p>
                    <p class="mt-1 text-sm text-muted">Add, enable or sync API sources for data and services.</p>
                </a>
                <a href="{{ route('admin.shop') }}" class="block rounded-card border border-line bg-surface p-5 hover:bg-surface-muted">
                    <p class="font-bold text-brand-navy">Super Mall catalog</p>
                    <p class="mt-1 text-sm text-muted">Products, variants, delivery zones and shop orders.</p>
                </a>
            </aside>
        </div>

        <section class="rounded-card border border-line bg-surface p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-extrabold text-brand-navy">Recent licenses</h2>
                <a href="{{ route('developer.licenses') }}" class="text-sm font-bold text-brand-navy underline underline-offset-4">View all</a>
            </div>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="border-b border-line bg-surface-muted">
                        <tr>
                            <th class="px-3 py-2 text-xs font-bold uppercase tracking-wide text-muted">Key</th>
                            <th class="px-3 py-2 text-xs font-bold uppercase tracking-wide text-muted">Client</th>
                            <th class="px-3 py-2 text-xs font-bold uppercase tracking-wide text-muted">Domain</th>
                            <th class="px-3 py-2 text-xs font-bold uppercase tracking-wide text-muted">Status</th>
                            <th class="px-3 py-2 text-xs font-bold uppercase tracking-wide text-muted">Expires</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($recentLicenses as $license)
                            <tr>
                                <td class="px-3 py-2 font-mono text-xs text-brand-navy">{{ $license->key_prefix }}</td>
                                <td class="px-3 py-2 font-semibold text-brand-navy">{{ $license->client_name }}</td>
                                <td class="px-3 py-2 text-muted">{{ $license->domain ?: 'Any' }}</td>
                                <td class="px-3 py-2 font-semibold text-brand-navy">{{ $license->statusLabel() }}</td>
                                <td class="px-3 py-2 text-muted">{{ $license->expires_at?->format('d M Y') ?: 'No expiry' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-6 text-center text-sm text-muted">No licenses issued yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-layouts.admin>
