<x-layouts.public title="Contact" description="Contact {{ \App\Support\Brand::name() }} customer support.">
    <div class="container-page py-10">
        <div class="mx-auto max-w-3xl">
            <h1 class="text-3xl font-extrabold tracking-tight text-brand-navy">Contact us</h1>
            <p class="mt-2 text-sm text-muted">Customer support for orders, delivery, returns and payment questions.</p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-card border border-line bg-surface p-5">
                    <h2 class="text-sm font-extrabold uppercase tracking-wide text-muted">Email</h2>
                    <p class="mt-2 text-base font-bold text-brand-navy"><a href="mailto:{{ config('store.support_email') }}" class="underline underline-offset-4">{{ config('store.support_email') }}</a></p>
                    <p class="mt-1 text-xs text-muted">Include your order number when asking about an order.</p>
                </div>
                <div class="rounded-card border border-line bg-surface p-5">
                    <h2 class="text-sm font-extrabold uppercase tracking-wide text-muted">Phone</h2>
                    <p class="mt-2 text-base font-bold text-brand-navy"><a href="tel:{{ preg_replace('/\s+/', '', config('store.support_phone')) }}" class="underline underline-offset-4">{{ config('store.support_phone') }}</a></p>
                    <p class="mt-1 text-xs text-muted">Monday to Saturday, 9am to 6pm GMT.</p>
                </div>
            </div>

            <div class="mt-6 rounded-card border border-line bg-surface-muted p-5 text-sm leading-relaxed text-brand-navy">
                <p class="font-bold">Order tracking</p>
                <p class="mt-1">Use the <a href="{{ route('orders.shop') }}" class="underline underline-offset-4">tracking page</a> with your order number and phone number for status and payment information.</p>
            </div>

            <div class="mt-6 rounded-card border border-line bg-surface p-5 text-sm leading-relaxed text-muted">
                <p class="font-bold text-brand-navy">Business details</p>
                <p class="mt-1">{{ \App\Support\Brand::name() }}, Ghana. Registered business details and a physical support address will be published when confirmed.</p>
            </div>
        </div>
    </div>
</x-layouts.public>
