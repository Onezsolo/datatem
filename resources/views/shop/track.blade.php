<x-layouts.public title="Track an order" description="Track a shop order with your order number and phone number.">
    <div class="container-page py-10">
        <div class="mx-auto max-w-xl">
            <h1 class="text-3xl font-extrabold tracking-tight text-brand-navy">Track your order</h1>
            <p class="mt-2 text-sm leading-relaxed text-muted">
                Enter the order number from your receipt and the phone number used at checkout.
            </p>

            @if ($errors->any())
                <div class="mt-4 rounded-card border border-brand-red-strong bg-surface p-4" role="alert">
                    <ul class="list-inside list-disc text-sm text-brand-navy">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('shop.track.lookup') }}" method="POST" class="mt-6 rounded-card border border-line bg-surface p-5">
                @csrf
                <div>
                    <label for="order_number" class="block text-xs font-bold uppercase tracking-wide text-muted">Order number</label>
                    <input id="order_number" name="order_number" type="text" value="{{ old('order_number') }}" required placeholder="DT-XXXXXXXX" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
                </div>
                <div class="mt-4">
                    <label for="phone" class="block text-xs font-bold uppercase tracking-wide text-muted">Phone number</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required placeholder="0244000000" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
                </div>
                <div class="mt-5">
                    <x-ui.button type="submit" class="w-full">Find order</x-ui.button>
                </div>
            </form>

            <p class="mt-4 text-xs leading-relaxed text-muted">
                Cannot find your order? Contact {{ config('store.support_email') }} or {{ config('store.support_phone') }}.
            </p>
        </div>
    </div>
</x-layouts.public>
