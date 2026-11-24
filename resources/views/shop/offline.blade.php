<x-layouts.public title="Offline" description="You are offline.">
    <div class="container-page py-16">
        <div class="mx-auto max-w-xl rounded-card border border-line bg-surface-muted p-8 text-center">
            <h1 class="text-2xl font-extrabold text-brand-navy">You are offline</h1>
            <p class="mt-2 text-sm leading-relaxed text-muted">
                This page was saved for low-connectivity browsing. Cart changes, checkout and order tracking need a connection.
            </p>
            <div class="mt-5 flex flex-wrap justify-center gap-3">
                <x-ui.button href="{{ route('shop.index') }}">Try the home page</x-ui.button>
                <x-ui.button href="{{ route('shop.cart.show') }}" variant="outline">Open cart</x-ui.button>
            </div>
            <p class="mt-4 text-xs text-muted">Reconnect and refresh to see current prices and stock.</p>
        </div>
    </div>
</x-layouts.public>
