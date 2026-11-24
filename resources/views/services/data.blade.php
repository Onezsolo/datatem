<x-layouts.shop title="Data bundles">
    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Services / Data</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">Buy data</h1>
                <p class="mt-1 text-sm text-muted">Wallet balance: <span class="font-bold text-brand-navy">GHS {{ number_format($wallet, 2) }}</span>. <a href="{{ route('topup') }}" class="font-bold text-brand-navy underline underline-offset-4">Top up</a></p>
            </div>
            <a href="{{ route('services.index') }}" class="inline-flex min-h-11 items-center rounded-control border border-line bg-surface px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">All services</a>
        </header>

        @include('partials.guest-purchase-note')

        @if ($items->isEmpty())
            <div class="rounded-card border border-line bg-surface p-8 text-center">
                <p class="text-sm font-bold text-brand-navy">No data bundles available right now.</p>
                <p class="mt-1 text-sm text-muted">Run a catalog sync in the admin console or check back later.</p>
            </div>
        @else
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($items as $item)
                    <div class="rounded-card border border-line bg-surface p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-brand-navy">{{ $item->name }}</p>
                                <p class="mt-0.5 text-xs text-muted">{{ collect([$item->network, $item->validity])->filter()->implode(' · ') }}</p>
                            </div>
                            <x-ui.badge variant="neutral" size="sm">{{ $item->data_value ?: 'Bundle' }}</x-ui.badge>
                        </div>

                        <p class="mt-3 text-lg font-extrabold text-brand-navy">GHS {{ number_format((float) $item->price, 2) }}</p>

                        <form action="{{ route('services.data.store', $item) }}" method="POST" class="mt-3 flex gap-2">
                            @csrf
                            <label for="beneficiary-{{ $item->id }}" class="sr-only">Phone number</label>
                            <input id="beneficiary-{{ $item->id }}" name="beneficiary" type="tel" required placeholder="0244000000" class="h-11 min-w-0 flex-1 rounded-control border border-line px-3 text-sm">
                            <x-ui.button type="submit" size="sm">Buy</x-ui.button>
                        </form>
                        @error('beneficiary')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                        @error('wallet')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.shop>
