<x-layouts.shop title="WAEC PINs">
    <div class="space-y-6">
        <div>
            <a href="{{ route('services.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-brand-navy">
                <x-ui.icon name="chevron-left" :size="16" />
                All services
            </a>
            <div class="mt-3 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-brand-navy">WAEC PINs</h1>
                    <p class="mt-1 text-sm text-muted">Buy a results checker PIN.</p>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span class="text-muted">Wallet <span class="font-bold text-brand-navy">GHS {{ number_format($wallet, 2) }}</span></span>
                    <a href="{{ route('topup') }}" class="font-bold text-brand-navy underline underline-offset-4">Top up</a>
                </div>
            </div>
        </div>

        @include('partials.guest-purchase-note')

        @if ($items->isEmpty())
            <div class="rounded-card border border-line bg-surface p-8 text-center">
                <p class="text-sm font-bold text-brand-navy">No WAEC PINs available right now.</p>
                <p class="mt-1 text-sm text-muted">Inventory is synced automatically; check back later.</p>
            </div>
        @else
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($items as $item)
                    <div class="rounded-card border border-line bg-surface p-5">
                        <p class="text-sm font-bold text-brand-navy">{{ $item->name }}</p>
                        <p class="mt-0.5 text-xs text-muted">GHS {{ number_format((float) $item->price, 2) }} each</p>

                        <form action="{{ route('services.waec.store', $item) }}" method="POST" class="mt-3 flex items-end gap-2">
                            @csrf
                                    <input type="hidden" name="request_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                            <div>
                                <label for="quantity-{{ $item->id }}" class="block text-xs font-bold uppercase tracking-wide text-muted">Quantity</label>
                                <input id="quantity-{{ $item->id }}" name="quantity" type="number" min="1" max="10" value="1" required class="mt-1 h-11 w-24 rounded-control border border-line px-3 text-sm">
                            </div>
                            <x-ui.button type="submit" size="sm">Buy PIN(s)</x-ui.button>
                        </form>
                        @error('wallet')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.shop>
