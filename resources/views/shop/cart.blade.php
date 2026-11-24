<x-layouts.public title="Your cart">
    <div class="container-page py-8">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-brand-navy">Your cart</h1>
                <p class="mt-1 text-sm text-muted">Guest cart. No account required.</p>
            </div>
            @if ($cart && $cart->items->isNotEmpty())
                <a href="{{ route('shop.index') }}" class="text-sm font-bold text-brand-navy underline underline-offset-4">Continue shopping</a>
            @endif
        </div>

        @if (! $cart || $cart->items->isEmpty())
            <div class="mt-8 rounded-card border border-line bg-surface-muted p-8 text-center">
                <p class="text-lg font-extrabold text-brand-navy">Your cart is empty</p>
                <p class="mt-1 text-sm text-muted">Browse categories and add an item to continue.</p>
                <div class="mt-5"><x-ui.button href="{{ route('shop.index') }}">Start shopping</x-ui.button></div>
            </div>
        @else
            <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]">
                <ul class="space-y-3">
                    @foreach ($cart->items as $item)
                        <li class="rounded-card border border-line bg-surface p-4">
                            <div class="flex flex-wrap items-start gap-4">
                                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-control bg-surface-muted">
                                    @if ($item->product?->primaryImage)
                                        <img src="{{ asset('storage/'.$item->product->primaryImage->path) }}" alt="{{ $item->product->primaryImage->alt_text ?: $item->product->name }}" class="h-full w-full object-cover">
                                    @else
                                        <x-ui.icon name="bag" :size="24" class="text-brand-navy/25" />
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('shop.products.show', $item->product?->slug) }}" class="text-sm font-bold text-brand-navy hover:underline">{{ $item->product?->name }}</a>
                                    @if ($item->variant)
                                        <p class="mt-0.5 text-xs text-muted">{{ $item->variant->name }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-muted">{{ config('store.currency') }} {{ number_format((float) $item->price, 2) }} each</p>

                                    <div class="mt-3 flex flex-wrap items-end gap-2">
                                        <form action="{{ route('shop.cart.items.update', $item) }}" method="POST" class="flex items-end gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <div>
                                                <label for="quantity-{{ $item->id }}" class="block text-xs font-bold uppercase tracking-wide text-muted">Quantity</label>
                                                <input id="quantity-{{ $item->id }}" name="quantity" type="number" min="1" max="99" value="{{ $item->quantity }}" class="mt-1 h-10 w-20 rounded-control border border-line px-2 text-sm text-ink">
                                            </div>
                                            <x-ui.button type="submit" variant="outline" size="sm">Update</x-ui.button>
                                        </form>

                                        <form action="{{ route('shop.cart.items.destroy', $item) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button type="submit" variant="link" size="sm">Remove</x-ui.button>
                                        </form>
                                    </div>
                                </div>

                                <div class="text-right">
                                    <p class="text-sm font-extrabold text-brand-navy">{{ config('store.currency') }} {{ number_format((float) $item->total_price, 2) }}</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <aside class="h-fit rounded-card border border-line bg-surface p-5">
                    <h2 class="text-lg font-extrabold text-brand-navy">Summary</h2>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">Items</dt>
                            <dd class="font-semibold text-brand-navy">{{ $cart->item_count }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-t border-line pt-2">
                            <dt class="text-muted">Subtotal</dt>
                            <dd class="font-extrabold text-brand-navy">{{ config('store.currency') }} {{ number_format($cart->subtotal, 2) }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted">Delivery</dt>
                            <dd class="text-brand-navy">Calculated at checkout</dd>
                        </div>
                    </dl>
                    <div class="mt-4">
                        <x-ui.button href="{{ route('shop.checkout.show') }}" size="lg" class="w-full">Continue to checkout</x-ui.button>
                    </div>
                    <form action="{{ route('shop.cart.clear') }}" method="POST" class="mt-3">
                        @csrf
                        @method('DELETE')
                        <x-ui.button type="submit" variant="link" size="sm">Clear cart</x-ui.button>
                    </form>
                </aside>
            </div>
        @endif
    </div>
</x-layouts.public>
