@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.dash-board { font-family: 'Outfit', sans-serif; }
.dash-board .dash-hero { background: linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%); }
.dash-board .dash-card { background: #ffffff; border: 1px solid #e2e8f0; }
.dash-board .dash-accent { color: #0891b2; }
.dash-board .dash-glow { box-shadow: 0 4px 24px -4px rgba(8, 145, 178, 0.15); }
.dash-board .dash-pill { background: #f1f5f9; border: 1px solid #e2e8f0; }
.dash-board .dash-pill:hover { background: rgba(8, 145, 178, 0.08); border-color: rgba(8, 145, 178, 0.35); }
.dash-board .dash-pkg { border-left: 3px solid; }
.dash-board .dash-pkg.mtn { border-color: #eab308; }
.dash-board .dash-pkg.at { border-color: #a855f7; }
.dash-board .dash-pkg.telecel { border-color: #ef4444; }
</style>
<div class="dash-board min-h-screen bg-gray-50 text-gray-900" x-data="userDashboard()" x-init="init()">
    <!-- Welcome modal (redesigned) -->
    <div x-show="showPopup" x-cloak class="fixed inset-0 z-[150] flex items-center justify-center p-4">
        <div x-show="showPopup" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div x-show="showPopup" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative w-full max-w-md bg-white border border-slate-200 rounded-2xl p-8 shadow-2xl">
            <div class="text-center">
                <div class="inline-flex w-16 h-16 rounded-xl bg-cyan-100 text-cyan-600 items-center justify-center mb-6 border border-cyan-200">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">You're in</h2>
                <p class="text-gray-500 text-sm mb-8">Your account is ready. Top up, buy data, and manage orders from here.</p>
                @php $whatsappChannelUrl = \App\Services\SettingsService::get('support_whatsapp_group_url') ?: ''; @endphp
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    @if($whatsappChannelUrl !== '')
                    <a href="{{ e($whatsappChannelUrl) }}" target="_blank" rel="noopener noreferrer" @click="showPopup = false; localStorage.setItem('welcomeShown', 'true')" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-[#25D366] text-white font-semibold text-sm hover:opacity-90 transition-opacity">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.414 0 0 5.414 0 12.05c0 2.123.551 4.2 1.6 6.04L0 24l6.101-1.601A11.77 11.77 0 0012.05 24.1c6.636 0 12.05-5.414 12.05-12.05 0-3.212-1.25-6.231-3.513-8.5zm-.002 0z"/></svg>
                        Join WhatsApp
                    </a>
                    @endif
                    <button @click="showPopup = false; localStorage.setItem('welcomeShown', 'true')" class="px-5 py-3 rounded-xl bg-slate-200 text-gray-800 font-semibold text-sm hover:bg-slate-300 transition-colors border border-slate-300">Continue</button>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 lg:py-12">
        <!-- Top: greeting + balance strip -->
        <header class="dash-hero rounded-2xl border border-slate-200 p-6 sm:p-8 mb-8 dash-glow">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                <div>
                    <p class="text-gray-500 text-sm font-medium uppercase tracking-wider mb-1">Dashboard</p>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900" x-text="'Hi, ' + (($store.auth.user.full_name || 'User').trim().split(/\s+/).pop() || 'User')"></h1>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-gray-500 text-sm font-medium">Balance</span>
                    <span class="dash-accent text-3xl sm:text-4xl font-bold tabular-nums" x-text="'GH₵ ' + (stats.balance != null ? Number(stats.balance).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00')"></span>
                </div>
            </div>
        </header>

        <!-- Reseller setup alert -->
        <div x-show="resellerNeedsStoreSetup" x-transition class="mb-8 p-4 rounded-xl bg-amber-50 border border-amber-200 flex flex-col sm:flex-row sm:items-center gap-4">
            <p class="text-amber-800 text-sm flex-1">Complete your reseller store setup to go live.</p>
            <a href="/reseller" class="shrink-0 px-4 py-2 rounded-lg bg-amber-100 text-amber-700 font-medium text-sm hover:bg-amber-200 transition-colors border border-amber-200">Complete setup</a>
        </div>

        <!-- Invite & Earn (affiliate) -->
        <div x-show="$store.auth.user && $store.auth.user.affiliate_system_enabled" x-transition class="mb-8 p-5 rounded-2xl dash-card border-cyan-200">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-cyan-100 text-cyan-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">Invite & Earn</p>
                        <p class="text-gray-500 text-sm">Share your link. Earn when others sign up.</p>
                    </div>
                    <span class="dash-accent font-bold text-lg">₵1 per signup</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a :href="whatsAppShareUrl" target="_blank" rel="noopener noreferrer" class="dash-pill px-4 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:text-gray-900 inline-flex items-center gap-2">WhatsApp</a>
                    <a :href="telegramShareUrl" target="_blank" rel="noopener noreferrer" class="dash-pill px-4 py-2.5 rounded-lg text-sm font-medium text-gray-700 hover:text-gray-900 inline-flex items-center gap-2">Telegram</a>
                    <a href="/referral" class="dash-pill px-4 py-2.5 rounded-lg text-sm font-medium text-cyan-600 hover:text-cyan-700 inline-flex items-center gap-2">My link</a>
                </div>
            </div>
        </div>

        <!-- Bento stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
            <div class="dash-card rounded-xl p-5">
                <p class="text-gray-500 text-xs font-medium uppercase tracking-wider mb-2">Today spent</p>
                <p class="text-xl font-bold text-gray-900 tabular-nums" x-text="'GH₵ ' + (stats.todaySpent != null ? Number(stats.todaySpent).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00')"></p>
            </div>
            <div class="dash-card rounded-xl p-5">
                <p class="text-gray-500 text-xs font-medium uppercase tracking-wider mb-2">Total spent</p>
                <p class="text-xl font-bold text-gray-900 tabular-nums" x-text="'GH₵ ' + (stats.totalSpent != null ? Number(stats.totalSpent).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00')"></p>
            </div>
            <div class="dash-card rounded-xl p-5">
                <p class="text-gray-500 text-xs font-medium uppercase tracking-wider mb-2">Orders</p>
                <p class="text-xl font-bold text-gray-900 tabular-nums" x-text="stats.totalOrders || 0"></p>
            </div>
            <div class="dash-card rounded-xl p-5 col-span-2 lg:col-span-1 flex flex-col justify-center">
                <a href="/topup" class="inline-flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-cyan-100 text-cyan-700 font-semibold text-sm border border-cyan-200 hover:bg-cyan-200 transition-colors">Add funds</a>
            </div>
        </div>

        <!-- Quick actions: pill row -->
        <nav class="flex flex-wrap gap-2 mb-10">
            <a href="/bundles" class="dash-pill px-5 py-3 rounded-xl font-medium text-sm text-gray-700 hover:text-gray-900 inline-flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Buy Data
            </a>
            <a href="/orders" class="dash-pill px-5 py-3 rounded-xl font-medium text-sm text-gray-700 hover:text-gray-900 inline-flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                Orders
            </a>
            <a href="/topup" class="dash-pill px-5 py-3 rounded-xl font-medium text-sm text-gray-700 hover:text-gray-900 inline-flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Top up
            </a>
            <a href="/support" class="dash-pill px-5 py-3 rounded-xl font-medium text-sm text-gray-700 hover:text-gray-900 inline-flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                Support
            </a>
        </nav>

        <!-- Popular packages: minimal list style -->
        <section class="mb-12">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-900">Popular packages</h2>
                <a href="/bundles" class="text-sm font-medium text-cyan-600 hover:text-cyan-700">View all</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="space-y-3">
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wider flex items-center gap-2">
                        <img src="{{ asset('assets/mtn.jpg') }}" alt="" class="w-5 h-5 rounded object-cover"> MTN
                    </p>
                    <template x-for="pkg in popularPackages.mtn" :key="pkg.id">
                        <div class="dash-card dash-pkg mtn rounded-lg p-4 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                            <span class="font-medium text-gray-900" x-text="pkg.name"></span>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-gray-600 text-sm font-medium" x-text="pkg.price_formatted"></span>
                                <button @click="openOrderModal(pkg)" type="button" class="px-3 py-1.5 rounded-lg bg-slate-200 text-gray-800 text-xs font-medium hover:bg-slate-300">Buy</button>
                            </div>
                        </div>
                    </template>
                    <p x-show="!popularPackages.mtn || popularPackages.mtn.length === 0" class="text-gray-500 text-sm py-2">No MTN packages</p>
                </div>
                <div class="space-y-3">
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wider flex items-center gap-2">
                        <img src="{{ asset('assets/airtelTigo.png') }}" alt="" class="w-5 h-5 rounded object-cover"> AirtelTigo
                    </p>
                    <template x-for="pkg in popularPackages.at" :key="pkg.id">
                        <div class="dash-card dash-pkg at rounded-lg p-4 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                            <span class="font-medium text-gray-900" x-text="pkg.name"></span>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-gray-600 text-sm font-medium" x-text="pkg.price_formatted"></span>
                                <button @click="openOrderModal(pkg)" type="button" class="px-3 py-1.5 rounded-lg bg-slate-200 text-gray-800 text-xs font-medium hover:bg-slate-300">Buy</button>
                            </div>
                        </div>
                    </template>
                    <p x-show="!popularPackages.at || popularPackages.at.length === 0" class="text-gray-500 text-sm py-2">No AirtelTigo packages</p>
                </div>
                <div class="space-y-3">
                    <p class="text-gray-500 text-xs font-medium uppercase tracking-wider flex items-center gap-2">
                        <img src="{{ asset('assets/telecel.png') }}" alt="" class="w-5 h-5 rounded object-cover"> Telecel
                    </p>
                    <template x-for="pkg in popularPackages.telecel" :key="pkg.id">
                        <div class="dash-card dash-pkg telecel rounded-lg p-4 flex items-center justify-between gap-4 hover:bg-slate-50 transition-colors">
                            <span class="font-medium text-gray-900" x-text="pkg.name"></span>
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-gray-600 text-sm font-medium" x-text="pkg.price_formatted"></span>
                                <button @click="openOrderModal(pkg)" type="button" class="px-3 py-1.5 rounded-lg bg-slate-200 text-gray-800 text-xs font-medium hover:bg-slate-300">Buy</button>
                            </div>
                        </div>
                    </template>
                    <p x-show="!popularPackages.telecel || popularPackages.telecel.length === 0" class="text-gray-500 text-sm py-2">No Telecel packages</p>
                </div>
            </div>
        </section>
    </div>

    <!-- Order modal (redesigned) -->
    <div x-show="showOrderModal" x-cloak class="fixed inset-0 z-[150] overflow-y-auto">
        <div x-show="showOrderModal" x-transition @click="showOrderModal = false" class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div x-show="showOrderModal" x-transition @click.stop class="relative w-full max-w-md bg-white border border-slate-200 rounded-2xl shadow-2xl overflow-hidden">
                <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <img :src="selectedPackage.logo" class="h-9 w-9 rounded-lg object-contain bg-slate-100 p-1" x-show="selectedPackage.logo" alt="">
                        <div>
                            <h3 class="font-semibold text-gray-900" x-text="(selectedPackage.network || '') + ' ' + (selectedPackage.name || '')"></h3>
                            <p class="text-gray-500 text-sm" x-text="selectedPackage.price_formatted || ''"></p>
                        </div>
                    </div>
                    <button @click="showOrderModal = false" type="button" class="p-2 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-6">
                    <label class="block text-gray-500 text-xs font-medium uppercase tracking-wider mb-2">Mobile number</label>
                    <input type="tel" x-model="orderBeneficiary" placeholder="0551234567" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-gray-900 placeholder-gray-400 focus:outline-none focus:border-cyan-500 font-medium">
                    <button @click="submitOrder()" :disabled="orderSubmitting" type="button" class="w-full mt-4 py-3.5 rounded-xl bg-cyan-500 text-white font-semibold hover:bg-cyan-600 disabled:opacity-50 transition-opacity">
                        <span x-text="orderSubmitting ? 'Processing…' : 'Place order'"></span>
                    </button>
                    <button @click="showOrderModal = false" type="button" class="w-full mt-3 py-2 text-gray-500 text-sm hover:text-gray-700">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function userDashboard() {
    const packageLogos = { MTN: '{{ asset('assets/mtn.jpg') }}', AT: '{{ asset('assets/airtelTigo.png') }}', Telecel: '{{ asset('assets/telecel.png') }}' };
    return {
        showPopup: false,
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
            const shouldShowWelcome = !localStorage.getItem('welcomeShown');
            await Promise.all([this.fetchStats(), this.fetchPopularPackages(), this.fetchResellerStatus()]);
            if (shouldShowWelcome) this.$nextTick(() => { this.showPopup = true; });
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
@endsection
