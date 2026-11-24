<x-layouts.shop :title="'Order '.$order->reference">
    <div class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Service order</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">{{ $order->reference }}</h1>
                <p class="mt-1 text-sm text-muted">Placed {{ $order->created_at?->format('d M Y, H:i') }}</p>
            </div>
            <a href="{{ route('services.orders') }}" class="inline-flex min-h-11 items-center rounded-control border border-line bg-surface px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">All service orders</a>
        </header>

        <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <div class="rounded-card border border-line bg-surface p-5">
                <dl class="space-y-3 text-sm">
                    <div class="flex flex-wrap justify-between gap-2 border-b border-line pb-2">
                        <dt class="text-muted">Service</dt>
                        <dd class="font-bold text-brand-navy">{{ $order->meta['item_name'] ?? ucfirst(str_replace('_', ' ', $order->remote_type)) }}</dd>
                    </div>
                    @if ($order->plan_code)
                        <div class="flex flex-wrap justify-between gap-2 border-b border-line pb-2">
                            <dt class="text-muted">Plan</dt>
                            <dd class="font-bold text-brand-navy">{{ $order->plan_code }}</dd>
                        </div>
                    @endif
                    @if ($order->beneficiary)
                        <div class="flex flex-wrap justify-between gap-2 border-b border-line pb-2">
                            <dt class="text-muted">Recipient</dt>
                            <dd class="font-bold text-brand-navy">{{ $order->beneficiary }}</dd>
                        </div>
                    @endif
                    <div class="flex flex-wrap justify-between gap-2 border-b border-line pb-2">
                        <dt class="text-muted">Amount</dt>
                        <dd class="font-bold text-brand-navy">GHS {{ number_format((float) $order->amount, 2) }}</dd>
                    </div>
                    <div class="flex flex-wrap justify-between gap-2 border-b border-line pb-2">
                        <dt class="text-muted">Status</dt>
                        <dd class="font-bold capitalize text-brand-navy">{{ $order->status }}</dd>
                    </div>
                    <div class="flex flex-wrap justify-between gap-2">
                        <dt class="text-muted">Payment</dt>
                        <dd class="font-bold capitalize text-brand-navy">{{ $order->payment_status }}</dd>
                    </div>
                </dl>

                @if ($order->error_message)
                    <p class="mt-4 rounded-control border border-brand-red-strong bg-surface p-3 text-sm font-semibold text-brand-red-strong">{{ $order->error_message }}</p>
                @endif

                @if (is_array($order->meta['remote']['gift_card'] ?? null))
                    <div class="mt-5 rounded-control border border-line bg-surface-muted p-4 text-sm">
                        <p class="font-bold text-brand-navy">Gift card details</p>
                        <p class="mt-2">Card: <span class="font-mono font-bold">{{ $order->meta['remote']['gift_card']['card_number'] ?? 'Pending' }}</span></p>
                        <p class="mt-1">PIN: <span class="font-mono font-bold">{{ $order->meta['remote']['gift_card']['pin_code'] ?? 'Pending' }}</span></p>
                    </div>
                @endif
            </div>

            <aside class="h-fit rounded-card border border-line bg-surface p-5 text-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Provider reference</p>
                <p class="mt-1 break-all font-bold text-brand-navy">{{ $order->remote_reference ?: 'Pending' }}</p>
                <p class="mt-3 text-xs leading-relaxed text-muted">Fulfilment is handled by our service provider. Status updates appear here; contact support with the reference if an order stalls.</p>
            </aside>
        </div>
    </div>
</x-layouts.shop>
