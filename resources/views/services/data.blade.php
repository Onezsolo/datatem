<x-layouts.shop title="Data bundles">
    <div class="space-y-6">
        <x-ui.page-heading title="Buy data" :subtitle="$items->isEmpty() ? 'Bundles delivered instantly.' : $items->count().' bundles across '. $groups->count().' networks.'" :back-url="route('services.index')">
            <x-slot:actions>
                <span class="text-muted">Wallet <span class="font-bold text-brand-navy">GHS {{ number_format($wallet, 2) }}</span></span>
                <a href="{{ route('topup') }}" class="font-bold text-brand-navy underline underline-offset-4">Top up</a>
            </x-slot:actions>
        </x-ui.page-heading>
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
                            :aria-selected="active === @js((string) $network)"
                            class="service-tab"
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
                                    <input type="hidden" name="request_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
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
