<x-layouts.public title="Checkout">
    <div class="container-page py-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-brand-navy">Guest checkout</h1>
        <p class="mt-2 max-w-2xl text-sm text-muted">Enter your delivery details. No account is created; use your order number and phone number to track the order.</p>

        @if ($errors->any())
            <div class="mt-4 rounded-card border border-brand-red-strong bg-surface p-4" role="alert">
                <p class="text-sm font-bold text-brand-red-strong">Check the highlighted fields.</p>
                <ul class="mt-2 list-inside list-disc text-sm text-brand-navy">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
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
            <form action="{{ route('shop.checkout.store') }}" method="POST" class="rounded-card border border-line bg-surface p-5" data-checkout-form data-service="{{ (float) config('delivery.service_charge', 0) }}">
                @csrf
                <h2 class="text-lg font-extrabold text-brand-navy">Delivery details</h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="customer_name" class="block text-xs font-bold uppercase tracking-wide text-muted">Full name</label>
                        <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name') }}" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
                    </div>
                    <div>
                        <label for="customer_phone" class="block text-xs font-bold uppercase tracking-wide text-muted">Phone</label>
                        <input id="customer_phone" name="customer_phone" type="tel" value="{{ old('customer_phone') }}" required placeholder="0244000000" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
                    </div>
                    <div>
                        <label for="customer_email" class="block text-xs font-bold uppercase tracking-wide text-muted">Email (optional)</label>
                        <input id="customer_email" name="customer_email" type="email" value="{{ old('customer_email') }}" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="delivery_address" class="block text-xs font-bold uppercase tracking-wide text-muted">Delivery address</label>
                        <input id="delivery_address" name="delivery_address" type="text" value="{{ old('delivery_address') }}" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
                    </div>
                    <div>
                        <label for="delivery_city" class="block text-xs font-bold uppercase tracking-wide text-muted">City or town</label>
                        <input id="delivery_city" name="delivery_city" type="text" value="{{ old('delivery_city') }}" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
                    </div>
                    <div>
                        <label for="delivery_region" class="block text-xs font-bold uppercase tracking-wide text-muted">Region (optional)</label>
                        <input id="delivery_region" name="delivery_region" type="text" value="{{ old('delivery_region') }}" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
                    </div>
                    <div>
                        <label for="delivery_zone" class="block text-xs font-bold uppercase tracking-wide text-muted">Delivery zone</label>
                        <select id="delivery_zone" name="delivery_zone" required class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink" data-zone-select>
                            @foreach ($zones as $key => $zone)
                                <option value="{{ $key }}" data-fee="{{ (float) $zone['fee'] }}" @selected(old('delivery_zone', config('delivery.default_zone')) === $key)>
                                    {{ $zone['label'] }} - {{ config('store.currency') }} {{ number_format((float) $zone['fee'], 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="delivery_phone" class="block text-xs font-bold uppercase tracking-wide text-muted">Delivery phone (optional)</label>
                        <input id="delivery_phone" name="delivery_phone" type="tel" value="{{ old('delivery_phone') }}" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="delivery_notes" class="block text-xs font-bold uppercase tracking-wide text-muted">Delivery notes (optional)</label>
                        <textarea id="delivery_notes" name="delivery_notes" rows="3" class="mt-1 w-full rounded-control border border-line px-3 py-2 text-sm text-ink">{{ old('delivery_notes') }}</textarea>
                    </div>
                </div>

                <h2 class="mt-8 text-lg font-extrabold text-brand-navy">Payment</h2>
                <label class="mt-3 flex items-start gap-3 rounded-card border border-line p-4">
                    <input type="radio" name="payment_method" value="paystack" checked class="mt-1">
                    <span>
                        <span class="block text-sm font-bold text-brand-navy">Pay with Paystack</span>
                        <span class="mt-1 block text-xs leading-relaxed text-muted">Mobile Money and card are handled on Paystack. We never store card numbers or Mobile Money PINs.</span>
                    </span>
                </label>

                <div class="mt-6">
                    <x-ui.button type="submit" size="lg" class="w-full">Place order and pay</x-ui.button>
                </div>
                <p class="mt-2 text-xs text-muted">You will be redirected to Paystack to complete payment. Your order is saved before payment.</p>
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
                    $defaultZone = old('delivery_zone', config('delivery.default_zone'));
                    $defaultFee = (float) ($zones[$defaultZone]['fee'] ?? 0);
                    $service = (float) config('delivery.service_charge', 0);
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
</x-layouts.public>
