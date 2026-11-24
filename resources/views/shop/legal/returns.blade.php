<x-layouts.public title="Returns" description="How to request a return or refund from {{ \App\Support\Brand::name() }}.">
    <div class="container-page py-10">
        <div class="mx-auto max-w-3xl">
            <h1 class="text-3xl font-extrabold tracking-tight text-brand-navy">Returns and refunds</h1>
            <p class="mt-2 text-sm text-muted">How to report a damaged, defective or incorrect item.</p>

            <div class="mt-6 space-y-5 text-sm leading-relaxed text-ink">
                <section>
                    <h2 class="text-lg font-extrabold text-brand-navy">Contact support first</h2>
                    <p class="mt-2">Email {{ config('store.support_email') }} or call {{ config('store.support_phone') }} with your order number, a description of the issue and photos where possible. Keep the item and packaging until the case is reviewed.</p>
                </section>
                <section>
                    <h2 class="text-lg font-extrabold text-brand-navy">Eligibility</h2>
                    <p class="mt-2">Damaged, defective or incorrect items are eligible for review. Items must be unused and returned with their packaging. Personal-care and hygiene products may be excluded where required by health rules.</p>
                </section>
                <section>
                    <h2 class="text-lg font-extrabold text-brand-navy">Resolution</h2>
                    <p class="mt-2">Depending on the review, we may arrange a replacement, a return or a refund. Refunds are sent through the original payment method where possible; provider processing times apply. Delivery fees are refunded only where the issue was ours.</p>
                </section>
                <section>
                    <h2 class="text-lg font-extrabold text-brand-navy">Policy status</h2>
                    <p class="mt-2">The exact review window and non-returnable categories are being finalised by the merchant. Support will confirm the applicable terms for your order. This page will be updated when the final policy is approved.</p>
                </section>
            </div>

            <p class="mt-6 text-xs text-muted">Contact <a href="{{ route('legal.contact') }}" class="underline underline-offset-4">customer support</a> to start a return.</p>
        </div>
    </div>
</x-layouts.public>
