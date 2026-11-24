<x-layouts.admin title="Template licenses">
    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Developer</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">Template licenses</h1>
                <p class="mt-1 text-sm text-muted">Issue license keys to clients under agreement, revoke or reinstate them. Keys are stored hashed and shown once.</p>
            </div>
            <a href="{{ route('developer.dashboard') }}" class="inline-flex min-h-11 items-center rounded-control border border-line bg-surface px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">Developer console</a>
        </header>

        @if (session('status'))
            <div class="rounded-card border border-brand-yellow bg-surface p-4 text-sm font-semibold text-brand-navy" role="status">{{ session('status') }}</div>
        @endif

        @if ($issuedKey)
            <div class="rounded-card border border-brand-yellow bg-surface p-5">
                <p class="text-sm font-bold text-brand-navy">New license for {{ $issuedClient }}</p>
                <p class="mt-1 text-xs text-muted">Copy this key now — it will not be shown again.</p>
                <p class="mt-3 break-all rounded-control border border-line bg-surface-muted px-3 py-2 font-mono text-sm font-bold text-brand-navy">{{ $issuedKey }}</p>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[22rem_1fr]">
            <form action="{{ route('developer.licenses.issue') }}" method="POST" class="h-fit rounded-card border border-line bg-surface p-5">
                @csrf
                <h2 class="text-lg font-extrabold text-brand-navy">Issue license</h2>

                <div class="mt-4 space-y-3">
                    <div>
                        <label for="client_name" class="block text-sm font-bold text-brand-navy">Client name</label>
                        <input id="client_name" name="client_name" type="text" required value="{{ old('client_name') }}" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                        @error('client_name')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="client_email" class="block text-sm font-bold text-brand-navy">Client email</label>
                        <input id="client_email" name="client_email" type="email" value="{{ old('client_email') }}" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                    </div>
                    <div>
                        <label for="domain" class="block text-sm font-bold text-brand-navy">Domain</label>
                        <input id="domain" name="domain" type="text" placeholder="client.example.com" value="{{ old('domain') }}" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                    </div>
                    <div>
                        <label for="product_key" class="block text-sm font-bold text-brand-navy">Template product</label>
                        <select id="product_key" name="product_key" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                            <option value="">General license</option>
                            @foreach ($products as $productKey => $product)
                                <option value="{{ $productKey }}" @selected(old('product_key', request('product')) === $productKey)>{{ $product['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="license_tier" class="block text-sm font-bold text-brand-navy">License tier</label>
                        <select id="license_tier" name="license_tier" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                            <option value="">Select tier</option>
                            @foreach ($tiers as $tierKey => $tier)
                                <option value="{{ $tierKey }}" @selected(old('license_tier', request('tier')) === $tierKey)>{{ $tier['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-muted">Price is set from the product's tier pricing automatically.</p>
                        @error('license_tier')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="agreement_reference" class="block text-sm font-bold text-brand-navy">Agreement reference</label>
                        <input id="agreement_reference" name="agreement_reference" type="text" value="{{ old('agreement_reference') }}" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                    </div>
                    <div>
                        <label for="expires_at" class="block text-sm font-bold text-brand-navy">Expires on</label>
                        <input id="expires_at" name="expires_at" type="date" value="{{ old('expires_at') }}" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                        @error('expires_at')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="notes" class="block text-sm font-bold text-brand-navy">Notes</label>
                        <textarea id="notes" name="notes" rows="3" class="mt-1 w-full rounded-control border border-line px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <button type="submit" class="mt-4 min-h-10 w-full rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Issue license</button>
            </form>

            <div class="overflow-hidden rounded-card border border-line bg-surface">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="border-b border-line bg-surface-muted">
                            <tr>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Key</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Client</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Product / tier</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Domain</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Price</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Agreement</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Status</th>
                                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-muted">Expires</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse ($licenses as $license)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-brand-navy">{{ $license->key_prefix }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-brand-navy">{{ $license->client_name }}</p>
                                        <p class="text-xs text-muted">{{ $license->client_email }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-muted">
                                        {{ $products[$license->product_key]['name'] ?? 'General' }}
                                        @if ($license->license_tier)
                                            <span class="block text-xs text-muted">{{ $tiers[$license->license_tier]['label'] ?? $license->license_tier }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-muted">{{ $license->domain ?: 'Any' }}</td>
                                    <td class="px-4 py-3 font-semibold text-brand-navy">{{ $license->price ? \App\Services\TemplateProductService::currency().' '.number_format((float) $license->price, 2) : '—' }}</td>
                                    <td class="px-4 py-3 text-muted">{{ $license->agreement_reference ?: '—' }}</td>
                                    <td class="px-4 py-3 font-semibold text-brand-navy">{{ $license->statusLabel() }}</td>
                                    <td class="px-4 py-3 text-muted">{{ $license->expires_at?->format('d M Y') ?: 'No expiry' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($license->status === 'active')
                                            <form action="{{ route('developer.licenses.revoke', $license) }}" method="POST" onsubmit="return confirm('Revoke this license?')">
                                                @csrf
                                                <button type="submit" class="min-h-8 rounded-control border border-brand-red-strong px-2 text-xs font-bold text-brand-red-strong hover:bg-surface-muted">Revoke</button>
                                            </form>
                                        @else
                                            <form action="{{ route('developer.licenses.reinstate', $license) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="min-h-8 rounded-control border border-line px-2 text-xs font-bold text-brand-navy hover:bg-surface-muted">Reinstate</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="px-4 py-8 text-center text-sm text-muted">No licenses yet. Issue the first one.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $licenses->links() }}</div>
            </div>
        </div>
    </div>
</x-layouts.admin>
