<x-layouts.shop title="Utility bills">
    <div class="space-y-6">
        <x-ui.page-heading title="Utility bills" subtitle="Pay prepaid electricity and water." :back-url="route('services.index')">
            <x-slot:actions>
                <span class="text-muted">Wallet <span class="font-bold text-brand-navy">GHS {{ number_format($wallet, 2) }}</span></span>
                <a href="{{ route('topup') }}" class="font-bold text-brand-navy underline underline-offset-4">Top up</a>
            </x-slot:actions>
        </x-ui.page-heading>
@include('partials.guest-purchase-note')

        @if ($items->isEmpty())
            <div class="rounded-card border border-line bg-surface p-8 text-center">
                <p class="text-sm font-bold text-brand-navy">No utility services available right now.</p>
            </div>
        @else
            <div x-data="{ active: {{ $items->first()->id }} }">
                <div class="flex gap-2 overflow-x-auto border-b border-line pb-3" role="tablist" aria-label="Utility services">
                    @foreach ($items as $item)
                        <button
                            type="button"
                            role="tab"
                            @click="active = {{ $item->id }}"
                            :aria-selected="active === {{ $item->id }}"
                            class="service-tab"
                        >
                            {{ $item->name }}
                            @if ((float) ($item->meta['fee'] ?? 0) > 0)
                                <span class="text-xs font-semibold opacity-70">GHS {{ number_format((float) $item->meta['fee'], 2) }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                @foreach ($items as $item)
                    <div
                        x-show="active === {{ $item->id }}"
                        x-cloak
                        class="mt-4 max-w-2xl"
                        role="tabpanel"
                        aria-label="{{ $item->name }} bill payment"
                        x-data="{
                            customer: '', amount: '', customerName: '', address: '', looking: false, lookupError: '', account: null,
                            async lookup() {
                                this.looking = true; this.lookupError = ''; this.account = null;
                                try {
                                    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
                                    const response = await fetch('{{ route('services.utility.lookup') }}', {
                                        method: 'POST',
                                        credentials: 'same-origin',
                                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-XSRF-TOKEN': match ? decodeURIComponent(match[1]) : '' },
                                        body: JSON.stringify({ item_id: {{ $item->id }}, customer_number: this.customer })
                                    });
                                    const data = await response.json().catch(() => ({}));
                                    if (!response.ok) throw new Error(data.error || data.message || 'Lookup failed.');
                                    this.account = data.lookup || {};
                                    this.customerName = this.account.customer_name || '';
                                    if (this.account.amount_due) this.amount = this.account.amount_due;
                                } catch (error) {
                                    this.lookupError = error.message;
                                } finally {
                                    this.looking = false;
                                }
                            }
                        }"
                    >
                        <div class="rounded-card border border-line bg-surface p-5 sm:p-6">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <h2 class="text-base font-extrabold text-brand-navy">{{ $item->name }}</h2>
                                    <p class="mt-0.5 text-sm text-muted">{{ $item->meta['customer_label'] ?? 'Customer number' }}@if ((float) ($item->meta['fee'] ?? 0) > 0) · fee GHS {{ number_format((float) $item->meta['fee'], 2) }}@endif</p>
                                </div>
                                <x-ui.badge variant="neutral" size="sm">Bill payment</x-ui.badge>
                            </div>

                            <form action="{{ route('services.utility.store', $item) }}" method="POST" class="mt-4 space-y-3">
                                @csrf
                                <input type="hidden" name="request_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">

                                <div>
                                    <label for="beneficiary-{{ $item->id }}" class="block text-sm font-semibold text-brand-navy">{{ $item->meta['customer_label'] ?? 'Customer number' }}</label>
                                    <div class="mt-1 flex gap-2">
                                        <input id="beneficiary-{{ $item->id }}" name="beneficiary" type="text" x-model="customer" required placeholder="{{ $item->meta['customer_label'] ?? 'Customer number' }}" class="h-11 min-w-0 flex-1 rounded-control border border-line px-3 text-sm">
                                        <button type="button" @click="lookup()" :disabled="looking || !customer" class="min-h-11 rounded-control border border-line px-3 text-sm font-bold text-brand-navy hover:bg-surface-muted disabled:opacity-60" x-text="looking ? 'Checking...' : 'Verify'"></button>
                                    </div>
                                    <p x-show="account" x-cloak class="mt-1 text-xs font-semibold text-brand-navy" x-text="'Account: ' + (account && (account.customer_name || account.account) ? (account.customer_name || account.account) : 'verified')"></p>
                                    <p x-show="lookupError" x-cloak class="mt-1 text-xs font-semibold text-brand-red-strong" x-text="lookupError"></p>
                                </div>

                                <div>
                                    <label for="amount-{{ $item->id }}" class="block text-sm font-semibold text-brand-navy">Bill amount (GHS)</label>
                                    <input id="amount-{{ $item->id }}" name="amount" type="number" step="0.01" min="1" max="5000" x-model="amount" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label for="name-{{ $item->id }}" class="block text-sm font-semibold text-brand-navy">Customer name (optional)</label>
                                        <input id="name-{{ $item->id }}" name="customer_name" type="text" x-model="customerName" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                                    </div>
                                    <div>
                                        <label for="address-{{ $item->id }}" class="block text-sm font-semibold text-brand-navy">Address (optional)</label>
                                        <input id="address-{{ $item->id }}" name="address" type="text" x-model="address" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm">
                                    </div>
                                </div>

                                <button type="submit" class="mt-1 min-h-11 w-full rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Pay bill</button>
                                @error('wallet')<p class="text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                                @error('amount')<p class="text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.shop>
