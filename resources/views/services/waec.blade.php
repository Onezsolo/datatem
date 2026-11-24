<x-layouts.shop title="WAEC PINs">
    <div class="space-y-6">
        <x-ui.page-heading title="WAEC PINs" subtitle="Buy a results checker PIN." :back-url="route('services.index')">
            <x-slot:actions>
                <span class="text-muted">Wallet <span class="font-bold text-brand-navy">GHS {{ number_format($wallet, 2) }}</span></span>
                <a href="{{ route('topup') }}" class="font-bold text-brand-navy underline underline-offset-4">Top up</a>
            </x-slot:actions>
        </x-ui.page-heading>
@include('partials.guest-purchase-note')

        @if ($items->isEmpty())
            <div class="rounded-card border border-line bg-surface p-8 text-center">
                <p class="text-sm font-bold text-brand-navy">No WAEC PINs available right now.</p>
            </div>
        @else
            <div x-data="{ active: {{ $items->first()->id }} }">
                <div class="flex gap-2 overflow-x-auto border-b border-line pb-3" role="tablist" aria-label="WAEC PIN products">
                    @foreach ($items as $item)
                        <button
                            type="button"
                            role="tab"
                            @click="active = {{ $item->id }}"
                            :aria-selected="active === {{ $item->id }}"
                            class="service-tab"
                        >
                            {{ $item->name }}
                            <span class="text-xs font-semibold opacity-70">GHS {{ number_format((float) $item->price, 2) }}</span>
                        </button>
                    @endforeach
                </div>

                @foreach ($items as $item)
                    <div x-show="active === {{ $item->id }}" x-cloak class="mt-4 max-w-2xl" role="tabpanel" aria-label="{{ $item->name }}">
                        <div class="rounded-card border border-line bg-surface p-5 sm:p-6">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <h2 class="text-base font-extrabold text-brand-navy">{{ $item->name }}</h2>
                                    <p class="mt-0.5 text-sm text-muted">GHS {{ number_format((float) $item->price, 2) }} per PIN</p>
                                </div>
                                <x-ui.badge variant="neutral" size="sm">Results checker</x-ui.badge>
                            </div>

                            <form action="{{ route('services.waec.store', $item) }}" method="POST" class="mt-4">
                                @csrf
                                <input type="hidden" name="request_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">

                                <label for="quantity-{{ $item->id }}" class="block text-sm font-semibold text-brand-navy">How many PINs?</label>
                                <input id="quantity-{{ $item->id }}" name="quantity" type="number" min="1" max="10" value="1" required class="mt-1 h-11 w-24 rounded-control border border-line px-3 text-sm">
                                <p class="mt-1 text-xs text-muted">The PINs appear in your service order after delivery.</p>

                                <button type="submit" class="mt-4 min-h-11 w-full rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Buy PIN(s)</button>
                                @error('wallet')<p class="mt-2 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.shop>
