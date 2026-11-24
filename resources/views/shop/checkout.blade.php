<x-layouts.shop title="Checkout">
    @php
        $checkoutUser = auth()->user();
        $walletBalance = (float) ($checkoutUser?->balance ?? 0);
    @endphp
    <div class="container-page py-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-brand-navy">Checkout</h1>
        <p class="mt-2 max-w-2xl text-sm text-muted">Delivery details are saved to your account for next time. This order will appear in Shop orders.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-card border border-brand-red-strong bg-surface p-4" role="alert">
                <p class="text-sm font-bold text-brand-red-strong">Check the highlighted fields.</p>
                <ul class="mt-2 list-inside list-disc text-sm text-brand-navy">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                @if ($errors->has('wallet'))
                    <a href="{{ route('topup') }}" class="mt-3 inline-flex min-h-10 items-center rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Top up wallet</a>
                @endif
            </div>
        @endif

        @if ($messages !== [])
            <div class="mt-4 rounded-card border border-line bg-surface-muted p-4 text-sm text-brand-navy" role="status">
                @foreach ($messages as $message)
                    <p>{{ $message }}</p>
                @endforeach
            </div>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]">
            <form action="{{ route('shop.checkout.store') }}" method="POST" class="rounded-card border border-line bg-surface p-5" data-checkout-form data-service="{{ (float) $serviceCharge }}">
                @csrf
                <h2 class="text-lg font-extrabold text-brand-navy">Delivery details</h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="customer_name" class="block text-xs font-bold uppercase tracking-wide text-muted">Full name</label>
                        <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name', $checkoutUser?->delivery_name ?: $checkoutUser?->full_name) }}" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
                    </div>
                    <div>
                        <label for="customer_phone" class="block text-xs font-bold uppercase tracking-wide text-muted">Phone</label>
                        <input id="customer_phone" name="customer_phone" type="tel" value="{{ old('customer_phone', $checkoutUser?->delivery_phone ?: $checkoutUser?->phone) }}" required placeholder="0244000000" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
                    </div>
                    <div>
                        <label for="customer_email" class="block text-xs font-bold uppercase tracking-wide text-muted">Email (optional)</label>
                        <input id="customer_email" name="customer_email" type="email" value="{{ old('customer_email', $checkoutUser?->email) }}" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="delivery_address" class="block text-xs font-bold uppercase tracking-wide text-muted">Delivery address</label>
                        <input id="delivery_address" name="delivery_address" type="text" value="{{ old('delivery_address', $checkoutUser?->delivery_address) }}" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
                    </div>
                    <div>
                        <label for="delivery_city" class="block text-xs font-bold uppercase tracking-wide text-muted">City or town</label>
                        <input id="delivery_city" name="delivery_city" type="text" value="{{ old('delivery_city', $checkoutUser?->delivery_city) }}" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
                    </div>
                    <div>
                        <label for="delivery_region" class="block text-xs font-bold uppercase tracking-wide text-muted">Region (optional)</label>
                        <input id="delivery_region" name="delivery_region" type="text" value="{{ old('delivery_region', $checkoutUser?->delivery_region) }}" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
                    </div>
                    <div>
                        <label for="delivery_zone" class="block text-xs font-bold uppercase tracking-wide text-muted">Delivery zone</label>
                        <select id="delivery_zone" name="delivery_zone" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink" data-zone-select>
                            @foreach ($zones as $key => $zone)
                                <option value="{{ $key }}" data-fee="{{ (float) $zone['fee'] }}" @selected(old('delivery_zone', $checkoutUser?->delivery_zone ?: $defaultZone) === $key)>
                                    {{ $zone['label'] }} - {{ config('store.currency') }} {{ number_format((float) $zone['fee'], 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="delivery_phone" class="block text-xs font-bold uppercase tracking-wide text-muted">Delivery phone (optional)</label>
                        <input id="delivery_phone" name="delivery_phone" type="tel" value="{{ old('delivery_phone', $checkoutUser?->delivery_phone) }}" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="delivery_notes" class="block text-xs font-bold uppercase tracking-wide text-muted">Delivery notes (optional)</label>
                        <textarea id="delivery_notes" name="delivery_notes" rows="3" class="mt-1 w-full rounded-control border border-line px-3 py-2 text-sm text-ink">{{ old('delivery_notes', $checkoutUser?->delivery_notes) }}</textarea>
                    </div>
                </div>

                <h2 class="mt-8 text-lg font-extrabold text-brand-navy">Payment</h2>
                <input type="hidden" name="payment_method" value="wallet">
                <input type="hidden" name="checkout_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <div class="mt-3 rounded-card border border-line bg-surface-muted p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-bold text-brand-navy">Wallet balance</p>
                        <p class="text-sm font-extrabold text-brand-navy">GHS {{ number_format($walletBalance, 2) }}</p>
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-muted">
                        Shop orders are paid from your wallet. Top up before purchase if your balance is below the order total.
                    </p>
                    <a href="{{ route('topup') }}" class="mt-3 inline-flex min-h-10 items-center rounded-control border border-line bg-surface px-3 text-sm font-bold text-brand-navy hover:bg-surface-muted">Top up wallet</a>
                </div>

                <div class="mt-6">
                    <x-ui.button type="submit" size="lg" class="w-full">Pay from wallet</x-ui.button>
                </div>
                <p class="mt-2 text-xs text-muted">The order total is deducted from your wallet immediately and the order is created as paid.</p>
            </form>

            <aside class="h-fit rounded-card border border-line bg-surface p-5">
                <h2 class="text-lg font-extrabold text-brand-navy">Order summary</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    @foreach ($cart->items as $item)
                        <li class="flex justify-between gap-3">
                            <span class="min-w-0 text-brand-navy">
                                {{ $item->product?->name }}
                                @if ($item->variant)
                                    <span class="block text-xs text-muted">{{ $item->variant->name }}</span>
                                @endif
                                <span class="block text-xs text-muted">Qty {{ $item->quantity }}</span>
                            </span>
                            <span class="shrink-0 font-semibold text-brand-navy" data-line-total="{{ (float) $item->total_price }}">{{ config('store.currency') }} {{ number_format((float) $item->total_price, 2) }}</span>
                        </li>
                    @endforeach
                </ul>

                @php
                    $selectedZone = old('delivery_zone', $defaultZone);
                    $defaultFee = (float) ($zones[$selectedZone]['fee'] ?? 0);
                    $service = (float) $serviceCharge;
                    $subtotal = (float) $cart->subtotal;
                @endphp

                <dl class="mt-4 space-y-2 border-t border-line pt-4 text-sm" data-checkout-summary data-subtotal="{{ $subtotal }}">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Subtotal</dt>
                        <dd class="font-semibold text-brand-navy">{{ config('store.currency') }} <span data-checkout-subtotal>{{ number_format($subtotal, 2) }}</span></dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Delivery</dt>
                        <dd class="font-semibold text-brand-navy">{{ config('store.currency') }} <span data-checkout-delivery>{{ number_format($defaultFee, 2) }}</span></dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Service charge</dt>
                        <dd class="font-semibold text-brand-navy">{{ config('store.currency') }} <span data-checkout-service>{{ number_format($service, 2) }}</span></dd>
                    </div>
                    <div class="flex justify-between gap-3 border-t border-line pt-2 text-base">
                        <dt class="font-bold text-brand-navy">Total</dt>
                        <dd class="font-extrabold text-brand-navy">{{ config('store.currency') }} <span data-checkout-total>{{ number_format($subtotal + $defaultFee + $service, 2) }}</span></dd>
                    </div>
                </dl>

                <a href="{{ route('shop.cart.show') }}" class="mt-4 inline-block text-sm font-bold text-brand-navy underline underline-offset-4">Back to cart</a>
            </aside>
        </div>
    </div>
</x-layouts.shop>
