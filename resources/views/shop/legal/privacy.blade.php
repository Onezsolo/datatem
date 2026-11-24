<x-layouts.public title="Privacy" description="How {{ \App\Support\Brand::name() }} handles guest order data.">
    <div class="container-page py-10">
        <div class="mx-auto max-w-3xl">
            <h1 class="text-3xl font-extrabold tracking-tight text-brand-navy">Privacy</h1>
            <p class="mt-2 text-sm text-muted">Last updated {{ now()->format('j F Y') }}. This page explains how guest order data is handled.</p>

            <div class="mt-6 space-y-5 text-sm leading-relaxed text-ink">
                <section>
                    <h2 class="text-lg font-extrabold text-brand-navy">Data we collect</h2>
                    <p class="mt-2">We collect the name, phone number, optional email address, delivery address and order details you provide at checkout. We also record technical information such as IP address and browser type for security and troubleshooting.</p>
                </section>
                <section>
                    <h2 class="text-lg font-extrabold text-brand-navy">Payment data</h2>
                    <p class="mt-2">Payments are processed by Paystack. Novagel does not receive or store card numbers or Mobile Money PINs. We store payment references and status so orders can be reconciled and supported.</p>
                </section>
                <section>
                    <h2 class="text-lg font-extrabold text-brand-navy">How we use data</h2>
                    <p class="mt-2">We use order data to process and deliver orders, provide support, prevent fraud, meet accounting obligations and improve the storefront. We do not sell customer data.</p>
                </section>
                <section>
                    <h2 class="text-lg font-extrabold text-brand-navy">Sharing</h2>
                    <p class="mt-2">Data is shared only with payment and delivery providers needed to fulfil an order, and with service providers who help operate the store under appropriate confidentiality. We may disclose data where required by law.</p>
                </section>
                <section>
                    <h2 class="text-lg font-extrabold text-brand-navy">Retention and rights</h2>
                    <p class="mt-2">Order records are kept for accounting, dispute handling and support. To request access, correction or deletion of your data, contact {{ config('store.support_email') }} with your order number. Retention periods and the full privacy notice are subject to final legal review under the Ghana Data Protection Act 2012 (Act 843).</p>
                </section>
                <section>
                    <h2 class="text-lg font-extrabold text-brand-navy">Cookies</h2>
                    <p class="mt-2">The store uses a session cookie for the guest cart and administrator login. No advertising or cross-site tracking cookies are used in this version.</p>
                </section>
            </div>

            <p class="mt-6 text-xs text-muted">Questions: <a href="{{ route('legal.contact') }}" class="underline underline-offset-4">contact us</a> or email {{ config('store.support_email') }}.</p>
        </div>
    </div>
</x-layouts.public>
