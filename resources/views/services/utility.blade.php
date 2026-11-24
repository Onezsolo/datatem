<x-layouts.shop title="Utility bills">
    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Services / Utility</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">Utility bills</h1>
                <p class="mt-1 text-sm text-muted">Wallet balance: <span class="font-bold text-brand-navy">GHS {{ number_format($wallet, 2) }}</span>. Fees are added at checkout.</p>
            </div>
            <a href="{{ route('services.index') }}" class="inline-flex min-h-11 items-center rounded-control border border-line bg-surface px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">All services</a>
        </header>

        @include('partials.guest-purchase-note')

        @if ($items->isEmpty())
            <div class="rounded-card border border-line bg-surface p-8 text-center">
                <p class="text-sm font-bold text-brand-navy">No utility services available right now.</p>
            </div>
        @else
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($items as $item)
                    <div
                        class="rounded-card border border-line bg-surface p-5"
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
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-brand-navy">{{ $item->name }}</p>
                                <p class="mt-0.5 text-xs text-muted">{{ $item->meta['customer_label'] ?? 'Customer number' }} · fee GHS {{ number_format((float) ($item->meta['fee'] ?? 0), 2) }}</p>
                            </div>
                        </div>

                        <form action="{{ route('services.utility.store', $item) }}" method="POST" class="mt-3 space-y-2">
                            @csrf
                                    <input type="hidden" name="request_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                            <div class="flex gap-2">
                                <input name="beneficiary" type="text" x-model="customer" required placeholder="{{ $item->meta['customer_label'] ?? 'Customer number' }}" class="h-11 min-w-0 flex-1 rounded-control border border-line px-3 text-sm">
                                <button type="button" @click="lookup()" :disabled="looking || !customer" class="min-h-11 rounded-control border border-line px-3 text-sm font-bold text-brand-navy hover:bg-surface-muted disabled:opacity-60" x-text="looking ? '...' : 'Verify'"></button>
                            </div>
                            <p x-show="account" x-cloak class="text-xs font-semibold text-brand-navy" x-text="'Account: ' + (account && (account.customer_name || account.account) ? (account.customer_name || account.account) : 'verified')"></p>
                            <p x-show="lookupError" x-cloak class="text-xs font-semibold text-brand-red-strong" x-text="lookupError"></p>
                            <input name="amount" type="number" step="0.01" min="1" max="5000" x-model="amount" required placeholder="Bill amount (GHS)" class="h-11 w-full rounded-control border border-line px-3 text-sm">
                            <input name="customer_name" type="text" x-model="customerName" placeholder="Customer name (optional)" class="h-11 w-full rounded-control border border-line px-3 text-sm">
                            <input name="address" type="text" x-model="address" placeholder="Address (optional)" class="h-11 w-full rounded-control border border-line px-3 text-sm">
                            <x-ui.button type="submit" size="sm" class="w-full">Pay bill</x-ui.button>
                        </form>
                        @error('wallet')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                        @error('amount')<p class="mt-1 text-xs font-semibold text-brand-red-strong">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.shop>
