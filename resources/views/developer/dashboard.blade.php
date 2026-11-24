<x-layouts.admin title="Developer console">
    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Developer</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">Developer console</h1>
                <p class="mt-1 text-sm text-muted">Set up the template package, license the deployment and hand it over. The business itself belongs to the client admin.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('developer.packages') }}" class="inline-flex min-h-11 items-center rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Template packages</a>
                <a href="{{ route('developer.licenses') }}" class="inline-flex min-h-11 items-center rounded-control border border-line bg-surface px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Licenses</a>
            </div>
        </header>

        @if (session('status'))
            <div class="rounded-card border border-brand-yellow bg-surface p-4 text-sm font-semibold text-brand-navy" role="status">{{ session('status') }}</div>
        @endif

        @if (session('warning'))
            <div class="rounded-card border border-brand-red-strong bg-surface p-4 text-sm font-semibold text-brand-navy" role="alert">{{ session('warning') }}</div>
        @endif

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-card border border-line bg-surface p-5">
                <p class="text-[0.625rem] font-bold uppercase tracking-wider text-muted">Template packages</p>
                <p class="mt-1 text-2xl font-extrabold text-brand-navy">{{ $packagesCount }}</p>
                <p class="mt-1 text-xs text-muted">{{ $appliedPackage ? 'Applied: '.$appliedPackage['name'].' v'.$appliedPackage['version'] : 'Not applied yet' }}</p>
            </div>
            <div class="rounded-card border border-line bg-surface p-5">
                <p class="text-[0.625rem] font-bold uppercase tracking-wider text-muted">Active licenses</p>
                <p class="mt-1 text-2xl font-extrabold text-brand-navy">{{ $licensesActive }}</p>
                <p class="mt-1 text-xs text-muted">{{ $licensesTotal }} issued · {{ $licensesRevoked }} revoked</p>
            </div>
            <div class="rounded-card border border-line bg-surface p-5">
                <p class="text-[0.625rem] font-bold uppercase tracking-wider text-muted">Deployment license</p>
                <p class="mt-1 text-lg font-extrabold capitalize text-brand-navy">{{ $deployment['state'] }}</p>
                <p class="mt-1 text-xs text-muted">{{ $deployment['prefix'] ?: 'No key set' }}</p>
            </div>
            <div class="rounded-card border border-line bg-surface p-5">
                <p class="text-[0.625rem] font-bold uppercase tracking-wider text-muted">Handover</p>
                <p class="mt-1 text-lg font-extrabold capitalize text-brand-navy">{{ $handover['completed'] ? 'Completed' : 'Not completed' }}</p>
                <p class="mt-1 text-xs text-muted">{{ $handover['completed'] ? 'Developer access ended' : 'Developer still has access' }}</p>
            </div>
        </div>

        <section class="rounded-card border border-line bg-surface p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-extrabold text-brand-navy">Template products</h2>
                    <p class="mt-1 text-sm text-muted">Each product line configures modules, services and design for its own template.</p>
                </div>
                <a href="{{ route('developer.products') }}" class="text-sm font-bold text-brand-navy underline underline-offset-4">Manage products</a>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                @foreach ($productVariants as $key => $variant)
                    @php($isActive = ($activeProduct['key'] ?? null) === $key)
                    <div class="flex h-full flex-col rounded-control border p-3 {{ $isActive ? 'border-brand-navy bg-surface-muted' : 'border-line bg-surface' }}">
                        <p class="text-sm font-bold text-brand-navy">{{ $variant['name'] }}</p>
                        <p class="mt-1 text-[0.6875rem] leading-relaxed text-muted">{{ $variant['description'] }}</p>
                        <p class="mt-2 text-[0.625rem] font-bold uppercase tracking-wide text-muted">{{ $isActive ? 'Active template' : (\App\Services\ThemeSettings::presets()[$variant['theme']]['label'] ?? $variant['theme']) }}</p>
                        <form action="{{ route('developer.products.apply', $key) }}" method="POST" class="mt-auto pt-3" onsubmit="return confirm('Apply the {{ $variant['name'] }} template to this deployment?')">
                            @csrf
                            <button type="submit" class="min-h-8 w-full rounded-control text-xs font-bold {{ $isActive ? 'border border-line bg-surface text-brand-navy hover:bg-surface-muted' : 'bg-brand-yellow text-brand-navy hover:bg-brand-yellow-strong' }}">
                                {{ $isActive ? 'Re-apply' : 'Set up' }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>
        <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
            <section class="space-y-6">
                <div class="rounded-card border border-line bg-surface p-5">
                    <h2 class="text-lg font-extrabold text-brand-navy">Deployment license</h2>
                    <p class="mt-1 text-sm text-muted">Set the license issued for this installation. Keep the license key private.</p>

                    @if ($deployment['license'])
                        <dl class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
                            <div><dt class="text-muted">Client</dt><dd class="font-bold text-brand-navy">{{ $deployment['license']->client_name }}</dd></div>
                            <div><dt class="text-muted">Status</dt><dd class="font-bold text-brand-navy">{{ $deployment['license']->statusLabel() }}</dd></div>
                            <div><dt class="text-muted">Domain</dt><dd class="font-bold text-brand-navy">{{ $deployment['license']->domain ?: 'Any domain' }}</dd></div>
                            <div><dt class="text-muted">Expires</dt><dd class="font-bold text-brand-navy">{{ $deployment['license']->expires_at?->format('d M Y') ?: 'No expiry' }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-muted">Agreement</dt><dd class="font-bold text-brand-navy">{{ $deployment['license']->agreement_reference ?: '—' }}</dd></div>
                        </dl>
                    @endif

                    <form action="{{ route('developer.deployment-license') }}" method="POST" class="mt-4">
                        @csrf
                        <label for="license_key" class="block text-sm font-bold text-brand-navy">License key</label>
                        <input id="license_key" name="license_key" type="text" required placeholder="DTMP-XXXX-XXXX-XXXX-XXXX" class="mt-1 h-11 w-full rounded-control border border-line px-3 font-mono text-sm">
                        @error('license_key')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                        <button type="submit" class="mt-3 min-h-10 rounded-control bg-brand-navy px-4 text-sm font-bold text-white hover:bg-brand-navy-soft">Set deployment license</button>
                    </form>
                </div>

                <div class="rounded-card border border-line bg-surface p-5">
                    <h2 class="text-lg font-extrabold text-brand-navy">Handover</h2>
                    <p class="mt-1 text-sm text-muted">Complete handover after applying the template package and license. Developer console, admin access and developer login are disabled permanently for this deployment. The client admin keeps running the business.</p>

                    @if ($deployment['state'] === 'active')
                        <form action="{{ route('developer.handover') }}" method="POST" class="mt-4 space-y-3" onsubmit="return confirm('Complete handover and permanently disable developer access on this deployment?')">
                            @csrf
                            <label for="confirmation" class="block text-sm font-bold text-brand-navy">Type HANDOVER to confirm</label>
                            <input id="confirmation" name="confirmation" type="text" required placeholder="HANDOVER" class="h-11 w-full rounded-control border border-line px-3 font-mono text-sm">
                            @error('confirmation')<p class="text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                            <label for="note" class="block text-sm font-bold text-brand-navy">Handover note</label>
                            <textarea id="note" name="note" rows="2" class="w-full rounded-control border border-line px-3 py-2 text-sm" placeholder="Agreement reference, support terms..."></textarea>
                            <button type="submit" class="min-h-10 rounded-control bg-brand-red-strong px-4 text-sm font-bold text-white hover:bg-brand-red-strong-hover">Complete handover</button>
                        </form>
                    @else
                        <p class="mt-4 rounded-control border border-line bg-surface-muted p-3 text-sm font-semibold text-muted">Set an active deployment license before completing handover.</p>
                    @endif
                </div>
            </section>

            <aside class="space-y-3">
                <div class="rounded-card border border-line bg-surface p-5">
                    <p class="text-xs font-bold uppercase tracking-wide text-muted">Setup flow</p>
                    <ol class="mt-2 space-y-2 text-sm text-muted">
                        <li>1. Sign in and configure providers/services (developer-only).</li>
                        <li>2. Save the setup as a template package.</li>
                        <li>3. Issue a license for the client agreement.</li>
                        <li>4. Set the deployment license.</li>
                        <li>5. Complete handover and hand the business to the client admin.</li>
                    </ol>
                </div>
                <a href="{{ route('developer.licenses') }}" class="block rounded-card border border-line bg-surface p-5 hover:bg-surface-muted">
                    <p class="font-bold text-brand-navy">Manage licenses</p>
                    <p class="mt-1 text-sm text-muted">Issue, revoke or reinstate client licenses.</p>
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
