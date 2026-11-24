<x-layouts.public title="Terms" description="Terms for shopping with {{ \App\Support\Brand::name() }}.">
    <div class="container-page py-10">
        <div class="mx-auto max-w-3xl">
            <h1 class="text-3xl font-extrabold tracking-tight text-brand-navy">Terms of sale</h1>
            <p class="mt-2 text-sm text-muted">Last updated {{ now()->format('j F Y') }}.</p>

            <div class="mt-6 space-y-5 text-sm leading-relaxed text-ink">
                <section>
                    <h2 class="text-lg font-extrabold text-brand-navy">Orders</h2>
                    <p class="mt-2">Orders are placed as a guest; no shopper account is created. An order is accepted when it is confirmed and payment is successfully verified. We may cancel an order if stock is unavailable, payment fails or fraud is suspected.</p>
                </section>
                <section>
                    <h2 class="text-lg font-extrabold text-brand-navy">Prices and payment</h2>
                    <p class="mt-2">Prices are shown in Ghana cedis (GHS) and include applicable taxes unless stated otherwise. Delivery fees are calculated by zone before payment. Payments are processed by Paystack; we do not store card numbers or Mobile Money PINs.</p>
                </section>
                <section>
                    <h2 class="text-lg font-extrabold text-brand-navy">Delivery</h2>
                    <p class="mt-2">Delivery estimates depend on the zone and courier availability. You are responsible for providing an accurate address and reachable phone number. Failed delivery attempts may require re-delivery at an additional cost.</p>
                </section>
                <section>
                    <h2 class="text-lg font-extrabold text-brand-navy">Cancellations and returns</h2>
                    <p class="mt-2">Pending orders can be cancelled before dispatch. Returns are handled under the returns process. Refunds are returned through the original payment method where possible.</p>
                </section>
                <section>
                    <h2 class="text-lg font-extrabold text-brand-navy">Liability and law</h2>
                    <p class="mt-2">We are responsible for delivering the products you order with reasonable care. We are not liable for indirect losses or delays outside our control. These terms are governed by the laws of Ghana. The full terms and returns policy are subject to final legal review.</p>
                </section>
            </div>

            <p class="mt-6 text-xs text-muted">Questions: <a href="{{ route('legal.contact') }}" class="underline underline-offset-4">contact us</a>.</p>
        </div>
    </div>
</x-layouts.public>
