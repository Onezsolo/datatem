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
            @php($firstNetwork = (string) $groups->keys()->first())

            <div x-data="{ active: @js($firstNetwork) }">
                <div class="flex gap-2 overflow-x-auto border-b border-line pb-3" role="tablist" aria-label="Networks">
                    @foreach ($groups as $network => $networkItems)
                        <button
                            type="button"
                            role="tab"
                            @click="active = @js((string) $network)"
                            :class="active === @js((string) $network) ? 'bg-brand-navy text-white border-brand-navy' : 'border-line bg-surface text-muted hover:text-brand-navy'"
                            :aria-selected="active === @js((string) $network)"
                            class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-control border px-4 text-sm font-bold"
                        >
                            {{ $network }}
                            <span class="text-xs font-semibold opacity-70">{{ $networkItems->count() }}</span>
                        </button>
                    @endforeach
                </div>

                @foreach ($groups as $network => $networkItems)
                    <div
                        x-show="active === @js((string) $network)"
                        x-cloak
                        class="mt-4 grid grid-cols-2 gap-3"
                        role="tabpanel"
                        aria-label="{{ $network }} bundles"
                    >
                        @foreach ($networkItems as $item)
                            <div class="flex h-full flex-col rounded-card border border-line bg-surface p-4 sm:p-5">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-brand-navy">{{ $item->name }}</p>
                                        <p class="mt-0.5 text-xs text-muted">{{ collect([$item->network, $item->validity])->filter()->implode(' · ') }}</p>
                                    </div>
                                    <x-ui.badge variant="neutral" size="sm">{{ $item->data_value ?: 'Bundle' }}</x-ui.badge>
                                </div>

                                <p class="mt-3 text-lg font-extrabold text-brand-navy">GHS {{ number_format((float) $item->price, 2) }}</p>

                                <form action="{{ route('services.data.store', $item) }}" method="POST" class="mt-auto flex flex-col gap-2 pt-3 sm:flex-row">
                                    @csrf
                                    <label for="beneficiary-{{ $item->id }}" class="sr-only">Phone number</label>
                                    <input id="beneficiary-{{ $item->id }}" name="beneficiary" type="tel" required placeholder="0244000000" class="h-11 min-w-0 flex-1 rounded-control border border-line px-3 text-sm">
                                    <x-ui.button type="submit" size="sm" class="w-full sm:w-auto">Buy</x-ui.button>
                                </form>
                                @error('beneficiary')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                                @error('wallet')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.shop>
