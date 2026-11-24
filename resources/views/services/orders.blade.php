<x-layouts.shop title="Service orders">
    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Orders</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">Service orders</h1>
                <p class="mt-1 text-sm text-muted">PATUPAA services you have purchased from your wallet.</p>
            </div>
            <a href="{{ route('services.index') }}" class="inline-flex min-h-11 items-center rounded-control border border-line bg-surface px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">All services</a>
        </header>

        @if ($orders->isEmpty())
            <div class="rounded-card border border-line bg-surface p-8 text-center">
                <p class="text-sm font-bold text-brand-navy">No service orders yet.</p>
                <p class="mt-1 text-sm text-muted">Buy data, WAEC PINs, utility bills, subscriptions or AFA registration to see them here.</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($orders as $order)
                    <a href="{{ route('services.orders.show', $order->reference) }}" class="block rounded-card border border-line bg-surface p-5 hover:bg-surface-muted">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-brand-navy">{{ $order->reference }}</p>
                                <p class="mt-0.5 text-xs text-muted">{{ $order->meta['item_name'] ?? ucfirst(str_replace('_', ' ', $order->remote_type)) }}{{ $order->beneficiary ? ' · '.$order->beneficiary : '' }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-extrabold text-brand-navy">GHS {{ number_format((float) $order->amount, 2) }}</span>
                                <span class="inline-flex min-h-7 items-center rounded-control bg-surface-muted px-2.5 text-xs font-bold capitalize text-brand-navy">{{ $order->status }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            <div>{{ $orders->links() }}</div>
        @endif
    </div>
</x-layouts.shop>
