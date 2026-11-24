<x-layouts.member title="Shop orders">
    <div
        class="space-y-6"
        x-data="{
            loading: true,
            orders: [],
            async init() {
                if (!this.$store.auth.token) {
                    window.location.href = '/login';
                    return;
                }
                try {
                    const response = await fetch('/api/auth/shop-orders', {
                        headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' }
                    });
                    if (response.ok) {
                        const data = await response.json();
                        this.orders = data.orders || [];
                    }
                } catch (e) {
                    console.error('Failed to load shop orders', e);
                } finally {
                    this.loading = false;
                }
            }
        }"
        x-init="init()"
    >
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Orders</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">Shop orders</h1>
                <p class="mt-1 text-sm text-muted">Paid from this account's wallet. Separate from data orders.</p>
            </div>
            <a href="{{ route('orders.index') }}" class="inline-flex min-h-11 items-center rounded-control border border-line bg-surface px-4 text-sm font-bold text-brand-navy hover:bg-surface-muted">
                Data orders
            </a>
        </header>

        <template x-if="loading">
            <p class="rounded-card border border-line bg-surface p-6 text-sm font-semibold text-muted">Loading shop orders...</p>
        </template>

        <template x-if="!loading && orders.length === 0">
            <div class="rounded-card border border-line bg-surface p-8 text-center">
                <p class="text-sm font-bold text-brand-navy">No shop orders yet.</p>
                <p class="mt-1 text-sm text-muted">Anything you buy from the shop appears here with its delivery status.</p>
                <x-ui.button :href="route('shop.index')" class="mt-4">Visit the shop</x-ui.button>
            </div>
        </template>

        <div class="space-y-3" x-show="!loading">
            <template x-for="order in orders" :key="order.order_number">
                <a :href="order.view_url" class="block rounded-card border border-line bg-surface p-5 hover:bg-surface-muted">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-brand-navy" x-text="order.order_number"></p>
                            <p class="mt-0.5 text-xs text-muted">
                                <span x-text="order.item_count"></span> item(s)
                                <span aria-hidden="true">&middot;</span>
                                <span x-text="order.placed_at ? new Date(order.placed_at).toLocaleDateString() : ''"></span>
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-extrabold text-brand-navy" x-text="(order.currency || 'GHS') + ' ' + Number(order.total).toFixed(2)"></span>
                            <span class="inline-flex min-h-7 items-center rounded-control bg-surface-muted px-2.5 text-xs font-bold capitalize text-brand-navy" x-text="order.status"></span>
                        </div>
                    </div>
                </a>
            </template>
        </div>
    </div>
</x-layouts.member>
