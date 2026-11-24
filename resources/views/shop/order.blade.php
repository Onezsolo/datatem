<x-layouts.shop :title="'Order '.$order->order_number">
    @php
        $statusVariant = match ($order->status) {
            \App\Shop\Models\Order::STATUS_PAID, \App\Shop\Models\Order::STATUS_PROCESSING => 'info',
            \App\Shop\Models\Order::STATUS_SHIPPED, \App\Shop\Models\Order::STATUS_DELIVERED => 'accent',
            \App\Shop\Models\Order::STATUS_CANCELLED, \App\Shop\Models\Order::STATUS_REFUNDED => 'danger',
            default => 'neutral',
        };
        $paymentVariant = match ($order->payment_status) {
            'paid' => 'accent',
            'failed', 'refunded' => 'danger',
            default => 'neutral',
        };
        $payUrl = $order->payment_status !== 'paid' && $order->status === \App\Shop\Models\Order::STATUS_PENDING
            ? \Illuminate\Support\Facades\URL::temporarySignedRoute('shop.payments.pay', now()->addDay(), ['order' => $order->order_number])
            : null;
        $lastPayment = $order->payments->sortByDesc('created_at')->first();
    @endphp

    <div class="container-page py-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Order {{ $order->order_number }}</p>
                <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-brand-navy">Thank you, {{ $order->customer_name }}</h1>
                <p class="mt-2 text-sm text-muted">Placed {{ $order->created_at?->format('j M Y, H:i') }} GMT</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <x-ui.badge :variant="$statusVariant">{{ ucfirst($order->status) }}</x-ui.badge>
                <x-ui.badge :variant="$paymentVariant">Payment {{ $order->payment_status }}</x-ui.badge>
            </div>
        </div>

        @if ($payUrl)
            <div class="mt-6 rounded-card border border-brand-yellow bg-surface p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-brand-navy">Payment is pending</p>
                        <p class="mt-1 text-sm text-muted">Complete payment to start processing. Total {{ $order->currency }} {{ number_format((float) $order->total, 2) }}.</p>
                    </div>
                    <x-ui.button :href="$payUrl" size="lg">Pay now</x-ui.button>
                </div>
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]">
            <section class="rounded-card border border-line bg-surface p-5" aria-labelledby="items-title">
                <h2 id="items-title" class="text-lg font-extrabold text-brand-navy">Items</h2>
                <ul class="mt-4 space-y-3">
                    @foreach ($order->items as $item)
                        <li class="flex justify-between gap-3 border-b border-line pb-3 text-sm last:border-0 last:pb-0">
                            <span class="min-w-0">
                                <span class="block font-bold text-brand-navy">{{ $item->product_name }}</span>
                                @if ($item->variant_name)
                                    <span class="block text-xs text-muted">{{ $item->variant_name }}</span>
                                @endif
                                <span class="block text-xs text-muted">{{ $item->product_sku }} - Qty {{ $item->quantity }}</span>
                            </span>
                            <span class="shrink-0 font-semibold text-brand-navy">{{ $order->currency }} {{ number_format((float) $item->total_price, 2) }}</span>
                        </li>
                    @endforeach
                </ul>

                <dl class="mt-4 space-y-2 border-t border-line pt-4 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Subtotal</dt>
                        <dd class="font-semibold text-brand-navy">{{ $order->currency }} {{ number_format((float) $order->subtotal, 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Delivery</dt>
                        <dd class="font-semibold text-brand-navy">{{ $order->currency }} {{ number_format((float) $order->delivery_fee, 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Service charge</dt>
                        <dd class="font-semibold text-brand-navy">{{ $order->currency }} {{ number_format((float) $order->service_charge, 2) }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-t border-line pt-2 text-base">
                        <dt class="font-bold text-brand-navy">Total</dt>
                        <dd class="font-extrabold text-brand-navy">{{ $order->currency }} {{ number_format((float) $order->total, 2) }}</dd>
                    </div>
                </dl>
            </section>

            <aside class="space-y-4">
                <section class="rounded-card border border-line bg-surface p-5">
                    <h2 class="text-sm font-extrabold uppercase tracking-wide text-muted">Delivery</h2>
                    <p class="mt-2 text-sm font-semibold text-brand-navy">{{ $order->customer_name }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-muted">
                        {{ $order->delivery_address }}<br>
                        {{ $order->delivery_city }}@if ($order->delivery_region), {{ $order->delivery_region }}@endif<br>
                        {{ $order->customer_phone }}
                    </p>
                    @if ($order->delivery_notes)
                        <p class="mt-2 text-xs leading-relaxed text-muted">Note: {{ $order->delivery_notes }}</p>
                    @endif
                </section>

                <section class="rounded-card border border-line bg-surface-muted p-5">
                    <h2 class="text-sm font-extrabold uppercase tracking-wide text-muted">Track this order</h2>
                    <p class="mt-2 text-sm leading-relaxed text-brand-navy">
                        Use order number <strong>{{ $order->order_number }}</strong> and your phone number on the
                        <a href="{{ route('shop.track') }}" class="underline underline-offset-4">tracking page</a>.
                    </p>
                    <p class="mt-2 text-xs leading-relaxed text-muted">
                        Keep this page open or save the order number. For payment issues, contact {{ config('store.support_email') }}.
                    </p>
                    @if ($lastPayment)
                        <p class="mt-3 text-xs text-muted">Payment reference: {{ $lastPayment->reference }}</p>
                    @endif
                </section>
            </aside>
        </div>
    </div>
</x-layouts.shop>
