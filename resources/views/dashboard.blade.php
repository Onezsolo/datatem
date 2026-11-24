<x-layouts.member title="Dashboard">

<style>
.dash-board { font-family: inherit; }
.dash-board .dash-hero { background: var(--color-surface-muted); border: 1px solid var(--color-line); }
.dash-board .dash-card { background: #ffffff; border: 1px solid #e2e8f0; }
.dash-board .dash-accent { color: var(--color-brand-navy); }
.dash-board .dash-glow { box-shadow: 0 4px 24px -4px rgba(0, 21, 52, 0.15); }
.dash-board .dash-pill { background: #f1f5f9; border: 1px solid #e2e8f0; }
.dash-board .dash-pill:hover { background: rgba(0, 21, 52, 0.08); border-color: rgba(0, 21, 52, 0.35); }
.dash-board .dash-pkg { border-left: 3px solid; }
.dash-board .dash-pkg.mtn { border-color: #eab308; }
.dash-board .dash-pkg.at { border-color: #a855f7; }
.dash-board .dash-pkg.telecel { border-color: #ef4444; }
</style>
<div class="dash-board min-h-screen bg-surface-muted text-brand-navy" x-data="userDashboard()" x-init="init()">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 lg:py-12">
        <!-- Top: greeting + balance strip -->
        <header class="dash-hero rounded-card border border-line p-6 sm:p-8 mb-8 dash-glow">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                <div>
                    <p class="text-muted text-sm font-medium uppercase tracking-wider mb-1">Dashboard</p>
                    <h1 class="text-2xl sm:text-3xl font-bold text-brand-navy" x-text="'Hi, ' + (($store.auth.user.full_name || 'User').trim().split(/\s+/).pop() || 'User')"></h1>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-muted text-sm font-medium">Balance</span>
                    <span class="dash-accent text-3xl sm:text-4xl font-bold tabular-nums" x-text="'GHS ' + (stats.balance != null ? Number(stats.balance).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00')"></span>
                </div>
            </div>
        </header>

        <!-- Reseller setup alert -->
        <div x-show="resellerNeedsStoreSetup" x-transition class="mb-8 p-4 rounded-card bg-amber-50 border border-amber-200 flex flex-col sm:flex-row sm:items-center gap-4">
            <p class="text-amber-800 text-sm flex-1">Complete your reseller store setup to go live.</p>
            <a href="/reseller" class="shrink-0 px-4 py-2 rounded-lg bg-amber-100 text-amber-700 font-medium text-sm hover:bg-amber-200 transition-colors border border-amber-200">Complete setup</a>
        </div>

        <!-- Invite & Earn (affiliate) -->
        <div x-show="$store.auth.user && $store.auth.user.affiliate_system_enabled" x-transition class="mb-8 p-5 rounded-card dash-card border-cyan-200">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-card bg-line text-brand-navy flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <p class="font-semibold text-brand-navy">Invite & Earn</p>
                        <p class="text-muted text-sm">Share your link. Earn when others sign up.</p>
                    </div>
                    <span class="dash-accent font-bold text-lg">GHS 1 per signup</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a :href="whatsAppShareUrl" target="_blank" rel="noopener noreferrer" class="dash-pill px-4 py-2.5 rounded-lg text-sm font-medium text-ink hover:text-brand-navy inline-flex items-center gap-2">WhatsApp</a>
                    <a :href="telegramShareUrl" target="_blank" rel="noopener noreferrer" class="dash-pill px-4 py-2.5 rounded-lg text-sm font-medium text-ink hover:text-brand-navy inline-flex items-center gap-2">Telegram</a>
                    <a href="/referral" class="dash-pill px-4 py-2.5 rounded-lg text-sm font-medium text-brand-navy hover:text-brand-navy-soft inline-flex items-center gap-2">My link</a>
                </div>
            </div>
        </div>

        <!-- Bento stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
            <div class="dash-card rounded-card p-5">
                <p class="text-muted text-xs font-medium uppercase tracking-wider mb-2">Today spent</p>
                <p class="text-xl font-bold text-brand-navy tabular-nums" x-text="'GHS ' + (stats.todaySpent != null ? Number(stats.todaySpent).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00')"></p>
            </div>
            <div class="dash-card rounded-card p-5">
                <p class="text-muted text-xs font-medium uppercase tracking-wider mb-2">Total spent</p>
                <p class="text-xl font-bold text-brand-navy tabular-nums" x-text="'GHS ' + (stats.totalSpent != null ? Number(stats.totalSpent).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00')"></p>
            </div>
            <div class="dash-card rounded-card p-5">
                <p class="text-muted text-xs font-medium uppercase tracking-wider mb-2">Orders</p>
                <p class="text-xl font-bold text-brand-navy tabular-nums" x-text="stats.totalOrders || 0"></p>
            </div>
            <div class="dash-card rounded-card p-5 col-span-2 lg:col-span-1 flex flex-col justify-center">
                <a href="/topup" class="inline-flex items-center justify-center gap-2 w-full py-3 rounded-card bg-line text-brand-navy-soft font-semibold text-sm border border-cyan-200 hover:bg-cyan-200 transition-colors">Add funds</a>
            </div>
        </div>

        <!-- Quick actions: pill row -->
        <nav class="flex flex-wrap gap-2 mb-10">
            <a href="/bundles" class="dash-pill px-5 py-3 rounded-card font-medium text-sm text-ink hover:text-brand-navy inline-flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5 text-brand-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Buy Data
            </a>
            <a href="/orders" class="dash-pill px-5 py-3 rounded-card font-medium text-sm text-ink hover:text-brand-navy inline-flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5 text-brand-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                Orders
            </a>
            <a href="/topup" class="dash-pill px-5 py-3 rounded-card font-medium text-sm text-ink hover:text-brand-navy inline-flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5 text-brand-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Top up
            </a>
            <a href="/support" class="dash-pill px-5 py-3 rounded-card font-medium text-sm text-ink hover:text-brand-navy inline-flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5 text-brand-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Support
            </a>
        </nav>

        <!-- Popular packages: minimal list style -->
        <section class="mb-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-brand-navy">Popular packages</h2>
                <a href="/bundles" class="text-sm font-medium text-brand-navy hover:text-brand-navy-soft">View all</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-3">
                    <p class="text-muted text-xs font-medium uppercase tracking-wider flex items-center gap-2">
                        <img src="{{ asset('assets/mtn.jpg') }}" alt="" class="w-5 h-5 rounded object-cover"> MTN
                    </p>
                    <template x-for="pkg in popularPackages.mtn" :key="pkg.id">
                        <div class="dash-card dash-pkg mtn rounded-lg p-4 flex items-center justify-between gap-4 hover:bg-surface-muted transition-colors">
                            <span class="font-medium text-brand-navy" x-text="pkg.name"></span>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-muted text-sm font-medium" x-text="pkg.price_formatted"></span>
                                <button @click="openOrderModal(pkg)" type="button" class="px-3 py-1.5 rounded-lg bg-slate-200 text-brand-navy text-xs font-medium hover:bg-slate-300">Buy</button>
                            </div>
                        </div>
                    </template>
                    <p x-show="!popularPackages.mtn || popularPackages.mtn.length === 0" class="text-muted text-sm py-2">No MTN packages</p>
                </div>
                <div class="space-y-3">
                    <p class="text-muted text-xs font-medium uppercase tracking-wider flex items-center gap-2">
                        <img src="{{ asset('assets/airtelTigo.png') }}" alt="" class="w-5 h-5 rounded object-cover"> AirtelTigo
                    </p>
                    <template x-for="pkg in popularPackages.at" :key="pkg.id">
                        <div class="dash-card dash-pkg at rounded-lg p-4 flex items-center justify-between gap-4 hover:bg-surface-muted transition-colors">
                            <span class="font-medium text-brand-navy" x-text="pkg.name"></span>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-muted text-sm font-medium" x-text="pkg.price_formatted"></span>
                                <button @click="openOrderModal(pkg)" type="button" class="px-3 py-1.5 rounded-lg bg-slate-200 text-brand-navy text-xs font-medium hover:bg-slate-300">Buy</button>
                            </div>
                        </div>
                    </template>
                    <p x-show="!popularPackages.at || popularPackages.at.length === 0" class="text-muted text-sm py-2">No AirtelTigo packages</p>
                </div>
                <div class="space-y-3">
                    <p class="text-muted text-xs font-medium uppercase tracking-wider flex items-center gap-2">
                        <img src="{{ asset('assets/telecel.png') }}" alt="" class="w-5 h-5 rounded object-cover"> Telecel
                    </p>
                    <template x-for="pkg in popularPackages.telecel" :key="pkg.id">
                        <div class="dash-card dash-pkg telecel rounded-lg p-4 flex items-center justify-between gap-4 hover:bg-surface-muted transition-colors">
                            <span class="font-medium text-brand-navy" x-text="pkg.name"></span>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-muted text-sm font-medium" x-text="pkg.price_formatted"></span>
                                <button @click="openOrderModal(pkg)" type="button" class="px-3 py-1.5 rounded-lg bg-slate-200 text-brand-navy text-xs font-medium hover:bg-slate-300">Buy</button>
                            </div>
                        </div>
                    </template>
                    <p x-show="!popularPackages.telecel || popularPackages.telecel.length === 0" class="text-muted text-sm py-2">No Telecel packages</p>
                </div>
            </div>
        </section>
    </div>

    <!-- Order modal (redesigned) -->
    <div x-show="showOrderModal" x-cloak class="fixed inset-0 z-[150] overflow-y-auto">
        <div x-show="showOrderModal" x-transition @click="showOrderModal = false" class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div x-show="showOrderModal" x-transition @click.stop class="relative w-full max-w-md bg-surface border border-line rounded-card shadow-none overflow-hidden">
                <div class="p-6 border-b border-line flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <img :src="selectedPackage.logo" class="h-9 w-9 rounded-lg object-contain bg-surface-muted p-1" x-show="selectedPackage.logo" alt="">
                        <div>
                            <h3 class="font-semibold text-brand-navy" x-text="(selectedPackage.network || '') + ' ' + (selectedPackage.name || '')"></h3>
                            <p class="text-muted text-sm" x-text="selectedPackage.price_formatted || ''"></p>
                        </div>
                    </div>
                    <button @click="showOrderModal = false" type="button" class="p-2 rounded-lg text-muted hover:text-brand-navy hover:bg-surface-muted transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <label class="block text-muted text-xs font-medium uppercase tracking-wider mb-2">Mobile number</label>
                    <input type="tel" x-model="orderBeneficiary" placeholder="0551234567" class="w-full px-4 py-3 rounded-card bg-surface-muted border border-line text-brand-navy placeholder-gray-400 focus:outline-none focus:border-brand-navy font-medium">
                    <button @click="submitOrder()" :disabled="orderSubmitting" type="button" class="w-full mt-4 py-3.5 rounded-card bg-brand-navy text-white font-semibold hover:bg-brand-navy disabled:opacity-50 transition-opacity">
                        <span x-text="orderSubmitting ? 'Processing…' : 'Place order'"></span>
                    </button>
                    <button @click="showOrderModal = false" type="button" class="w-full mt-3 py-2 text-muted text-sm hover:text-ink">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function userDashboard() {
    const packageLogos = { MTN: '{{ asset('assets/mtn.jpg') }}', AT: '{{ asset('assets/airtelTigo.png') }}', Telecel: '{{ asset('assets/telecel.png') }}' };
    return {
        stats: { balance: 0, todaySpent: 0, totalSpent: 0, totalOrders: 0 },
        popularPackages: { mtn: [], at: [], telecel: [] },
        showOrderModal: false,
        selectedPackage: { id: null, network: '', name: '', price_formatted: '', logo: '' },
        orderBeneficiary: '',
        orderSubmitting: false,
        resellerNeedsStoreSetup: false,
        get referralUrl() {
            const code = this.$store.auth.user && this.$store.auth.user.affiliate && this.$store.auth.user.affiliate.referral_code;
            return code ? window.location.origin + '/signup?ref=' + encodeURIComponent(code) : window.location.origin + '/signup';
        },
        get whatsAppShareUrl() {
            return 'https://wa.me/?text=' + encodeURIComponent('Get cheap data on R2BMarket! Sign up with my link: ' + this.referralUrl);
        },
        get telegramShareUrl() {
            return 'https://t.me/share/url?url=' + encodeURIComponent(this.referralUrl) + '&text=' + encodeURIComponent('Get cheap data on R2BMarket! Sign up with my link.');
        },
        async init() {
            if (!this.$store.auth.token) { window.location.href = '/login'; return; }
            await Promise.all([this.fetchStats(), this.fetchPopularPackages(), this.fetchResellerStatus()]);
        },
        async fetchResellerStatus() {
            try {
                const res = await fetch('/api/auth/reseller', { headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                this.resellerNeedsStoreSetup = (data.request && data.request.status === 'approved') && (data.store_setup_complete !== true);
            } catch (e) {}
        },
        async fetchStats() {
            try {
                const r = await fetch('{{ url("/api/auth/user/stats") }}', { headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' } });
                if (r.ok) this.stats = await r.json();
            } catch (e) { console.error(e); }
        },
        async fetchPopularPackages() {
            try {
                const r = await fetch('{{ url("/api/auth/user/popular-packages") }}', { headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' } });
                if (r.ok) this.popularPackages = await r.json();
            } catch (e) { console.error(e); }
        },
        openOrderModal(pkg) {
            this.selectedPackage = { id: pkg.id, network: pkg.network, name: pkg.name, price_formatted: pkg.price_formatted, logo: packageLogos[pkg.network] || '' };
            this.orderBeneficiary = '';
            this.showOrderModal = true;
        },
        async submitOrder() {
            if (!this.$store.auth.token) { window.location.href = '/login'; return; }
            if (!this.selectedPackage.id) return;
            const num = (this.orderBeneficiary || '').replace(/\s/g, '');
            if (!num || num.length < 10) { alert('Enter a valid Ghana mobile number (e.g. 0551234567).'); return; }
            this.orderSubmitting = true;
            try {
                const response = await fetch('{{ url("/api/auth/buy-data") }}', {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ package_id: this.selectedPackage.id, beneficiary: num })
                });
                const result = await response.json().catch(() => ({}));
                if (response.ok && !result.errors) {
                    this.showOrderModal = false;
                    window.location.href = '/orders?success=' + encodeURIComponent(result.message || (result.sent_to_api ? 'Data sent.' : 'Order received.'));
                    return;
                }
                alert((result.errors && result.errors.beneficiary && result.errors.beneficiary[0]) || result.message || 'Order failed. Try again.');
            } catch (e) { console.error(e); alert('Request failed.'); }
            finally { this.orderSubmitting = false; }
        }
    };
}
</script>
</x-layouts.member>
