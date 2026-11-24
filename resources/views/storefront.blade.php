@php
    $networkLogos = [
        'MTN' => asset('assets/mtn.jpg'),
        'AT' => asset('assets/airtelTigo.png'),
        'AirtelTigo' => asset('assets/airtelTigo.png'),
        'Telecel' => asset('assets/telecel.png'),
    ];
    $cardBgClasses = ['MTN' => 'bg-mtn', 'AT' => 'bg-airteltigo', 'AirtelTigo' => 'bg-airteltigo', 'Telecel' => 'bg-telecel'];
@endphp
@extends('layouts.storefront')

@section('content')
<div class="min-h-screen bg-surface-muted" x-data="storefrontPage({{ json_encode($slug) }})" x-init="init()"
     data-network-logos='@json($networkLogos)'
     data-card-bg='@json($cardBgClasses)'>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div x-show="errorMsg" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-card font-bold" x-text="errorMsg"></div>
        <div x-show="successMsg" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-card font-bold" x-text="successMsg"></div>

        <div x-show="!resellerName && !loading" class="text-center py-16">
            <p class="text-muted font-medium" x-text="errorMsg || 'Store not found.'"></p>
        </div>

        <template x-if="resellerName">
            <div>
                <!-- Hero -->
                <div class="text-center py-12 sm:py-16">
                    <h1 class="text-4xl sm:text-5xl font-black text-brand-navy tracking-tight">Buy data from <span class="text-brand-navy" x-text="resellerName"></span></h1>
                    <p class="mt-3 text-lg text-muted font-medium max-w-2xl mx-auto">No account needed. Choose a bundle, enter your number, pay with MoMo. Instant delivery.</p>
                </div>

                <!-- Track your order (walking customers) - visible at top -->
                <div class="mb-12 sm:mb-16 p-6 sm:p-8 bg-surface rounded-card border border-line shadow-none">
                    <h2 class="text-xl sm:text-2xl font-black text-brand-navy text-center mb-4">Track your order</h2>
                    <p class="text-center text-muted font-medium mb-6 max-w-lg mx-auto text-sm">Enter the mobile number you used when buying to see your order status.</p>
                    <div class="max-w-md mx-auto flex flex-col sm:flex-row gap-3">
                        <input type="tel"
                               x-model="trackNumber"
                               placeholder="e.g. 0551234567"
                               class="flex-1 px-4 py-3 rounded-card border border-line focus:border-primary-500 outline-none font-medium text-brand-navy">
                        <button type="button"
                                @click="trackOrders()"
                                :disabled="trackingOrders || !trackNumber.trim()"
                                class="px-6 py-3 rounded-card font-black bg-slate-900 text-white hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                            <span x-text="trackingOrders ? 'Checking…' : 'Track'"></span>
                        </button>
                    </div>
                    <div x-show="trackError" class="mt-4 max-w-md mx-auto p-4 bg-red-50 border border-red-200 text-red-700 rounded-card text-sm font-bold" x-text="trackError"></div>
                    <div x-show="trackOrdersList !== null && !trackOrdersList.length && trackNumber.trim() && !trackingOrders" class="mt-6 max-w-md mx-auto p-6 bg-surface-muted border border-line rounded-card text-center">
                        <p class="text-muted font-bold">No orders found for this number.</p>
                        <p class="text-muted text-sm mt-1">Use the number that received (or will receive) the data.</p>
                    </div>
                    <div x-show="trackOrdersList !== null && trackOrdersList.length" class="mt-8 max-w-2xl mx-auto space-y-4">
                        <template x-for="order in trackOrdersList" :key="order.reference">
                            <div class="bg-surface border border-line rounded-card p-6 shadow-none">
                                <div class="flex flex-wrap items-center justify-between gap-4">
                                    <div>
                                        <p class="font-black text-brand-navy" x-text="order.reference"></p>
                                        <p class="text-sm text-muted mt-1" x-text="(order.network || '') + ' ' + (order.data_plan || '')"></p>
                                        <p class="text-xs text-muted mt-1" x-text="order.created_at_formatted"></p>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-black text-brand-navy" x-text="'GHS ' + Number(order.amount).toFixed(2)"></span>
                                        <span class="block mt-2 px-3 py-1 rounded-full text-xs font-bold uppercase"
                                              :class="{
                                                  'bg-emerald-100 text-emerald-700': order.status === 'delivered',
                                                  'bg-amber-100 text-amber-700': order.status === 'processing' || order.status === 'pending',
                                                  'bg-red-100 text-red-700': order.status === 'failed',
                                                  'bg-surface-muted text-muted': !['delivered','processing','pending','failed'].includes(order.status)
                                              }"
                                              x-text="order.status"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Network tabs: MTN first, then AirtelTigo, Telecel, All -->
                <div x-show="!loading && packages.length" class="mb-10">
                    <div class="flex flex-wrap justify-center gap-2 p-1.5 bg-surface-muted rounded-[24px] w-fit mx-auto border border-line">
                        <button @click="activeNet = 'mtn'" :class="activeNet === 'mtn' ? 'bg-[#F0C232] text-brand-navy shadow-none' : 'text-muted hover:text-ink'" class="px-6 py-3 rounded-card text-sm font-bold transition-all">MTN</button>
                        <button @click="activeNet = 'at'" :class="activeNet === 'at' ? 'bg-[#8b5cf6] text-white shadow-none' : 'text-muted hover:text-ink'" class="px-6 py-3 rounded-card text-sm font-bold transition-all">AirtelTigo</button>
                        <button @click="activeNet = 'telecel'" :class="activeNet === 'telecel' ? 'bg-[#E0505B] text-white shadow-none' : 'text-muted hover:text-ink'" class="px-6 py-3 rounded-card text-sm font-bold transition-all">Telecel</button>
                        <button @click="activeNet = 'all'" :class="activeNet === 'all' ? 'bg-surface text-brand-navy shadow-none' : 'text-muted hover:text-ink'" class="px-6 py-3 rounded-card text-sm font-bold transition-all">All</button>
                    </div>
                </div>

                <div x-show="loading" class="text-center py-16">
                    <div class="animate-spin w-12 h-12 border-4 border-primary-600 border-t-transparent rounded-full mx-auto"></div>
                    <p class="mt-4 text-muted font-bold">Loading bundles...</p>
                </div>

                <!-- Packages grid (network-colored cards like Buy Data) -->
                <div x-show="!loading && packages.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-16">
                    <template x-for="pkg in filteredPackages" :key="pkg.id">
                        <div class="group relative bg-surface p-2 rounded-card border border-line shadow-none transition-all hover:-translate-y-1 hover:shadow-2xl">
                            <div class="rounded-[24px] p-6 h-full flex flex-col" :class="[getCardBgClass(pkg.network), isMtn(pkg.network) ? 'text-brand-navy' : 'text-white']">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="h-10 w-10 rounded-full overflow-hidden flex items-center justify-center bg-surface/20 border-2 border-white shadow-none">
                                        <img x-show="getNetworkLogo(pkg.network)" :src="getNetworkLogo(pkg.network)" :alt="pkg.network" class="h-full w-full object-contain p-0.5">
                                        <span x-show="!getNetworkLogo(pkg.network)" class="text-lg font-black" :class="isMtn(pkg.network) ? 'text-brand-navy' : 'text-white/90'" x-text="(pkg.network || '').charAt(0)"></span>
                                    </div>
                                    <span class="bg-slate-900 text-white px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest" x-text="pkg.volume_gb"></span>
                                </div>
                                <h3 class="text-lg font-black mb-1" x-text="(pkg.network || '') + ' ' + (pkg.volume_gb || '')"></h3>
                                <div class="mt-auto pt-4 flex items-center justify-between" :class="isMtn(pkg.network) ? 'border-t border-slate-900/20' : 'border-t border-white/20'">
                                    <span class="text-2xl font-black italic">GHS <span x-text="parseFloat(pkg.price).toFixed(2)"></span></span>
                                    <button type="button" @click="selectPackage(pkg)" class="bg-slate-900 text-white px-5 py-2.5 rounded-card text-sm font-black hover:bg-slate-800 transition-all shadow-none">Buy now</button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="!loading && packages.length && filteredPackages.length === 0" class="text-center py-12 rounded-card bg-surface-muted border border-line">
                    <p class="text-muted font-bold">No bundles in this category. Try another tab.</p>
                </div>

                <!-- How it works -->
                <div class="py-16 border-t border-line">
                    <h2 class="text-2xl font-black text-brand-navy text-center mb-10">How it works</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="text-center">
                            <div class="w-14 h-14 bg-primary-100 text-primary-600 rounded-card flex items-center justify-center mx-auto mb-4">
                                <span class="text-2xl font-black">1</span>
                            </div>
                            <h3 class="font-black text-brand-navy mb-2">Choose a bundle</h3>
                            <p class="text-muted text-sm font-medium">Pick MTN, AirtelTigo or Telecel and the size you need.</p>
                        </div>
                        <div class="text-center">
                            <div class="w-14 h-14 bg-primary-100 text-primary-600 rounded-card flex items-center justify-center mx-auto mb-4">
                                <span class="text-2xl font-black">2</span>
                            </div>
                            <h3 class="font-black text-brand-navy mb-2">Pay with MoMo</h3>
                            <p class="text-muted text-sm font-medium">Enter your number, then pay securely via Mobile Money.</p>
                        </div>
                        <div class="text-center">
                            <div class="w-14 h-14 bg-primary-100 text-primary-600 rounded-card flex items-center justify-center mx-auto mb-4">
                                <span class="text-2xl font-black">3</span>
                            </div>
                            <h3 class="font-black text-brand-navy mb-2">Get data instantly</h3>
                            <p class="text-muted text-sm font-medium">Data is delivered to your number within minutes.</p>
                        </div>
                    </div>
                </div>

                <!-- Why buy here -->
                <div class="py-16 bg-surface rounded-card border border-line shadow-none">
                    <h2 class="text-2xl font-black text-brand-navy text-center mb-10">Why buy here</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 max-w-4xl mx-auto">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-card bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div>
                                <p class="font-black text-brand-navy">Instant delivery</p>
                                <p class="text-muted text-sm">Data credited within minutes.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-card bg-primary-100 text-primary-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <div>
                                <p class="font-black text-brand-navy">Secure payment</p>
                                <p class="text-muted text-sm">Pay safely with MoMo.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-card bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="font-black text-brand-navy">No account needed</p>
                                <p class="text-muted text-sm">Just your mobile number.</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-card bg-surface-muted text-muted flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <div>
                                <p class="font-black text-brand-navy">Support on WhatsApp</p>
                                <p class="text-muted text-sm">Contact us from the menu.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Checkout modal -->
                <div x-show="selectedPackage" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-slate-900/60" @click="selectedPackage = null"></div>
                    <div class="relative bg-surface rounded-card shadow-2xl max-w-md w-full p-8">
                        <h3 class="text-xl font-black text-brand-navy mb-2">Complete purchase</h3>
                        <p class="text-muted font-medium mb-2" x-show="selectedPackage">
                            <span x-text="selectedPackage?.network"></span> <span x-text="selectedPackage?.volume_gb"></span> — GHS <span x-text="selectedPackage?.price?.toFixed(2)"></span>
                        </p>
                        <div class="mb-4 p-3 bg-surface-muted rounded-card border border-line text-sm">
                            <div class="flex justify-between text-muted mb-1"><span>Fee (3%)</span><span x-text="selectedPackage ? 'GHS ' + (selectedPackage.price * 0.03).toFixed(2) : ''"></span></div>
                            <div class="flex justify-between font-black text-brand-navy pt-1 border-t border-line"><span>Total to pay</span><span x-text="selectedPackage ? 'GHS ' + (selectedPackage.price * 1.03).toFixed(2) : ''"></span></div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-muted mb-1">Mobile number (receiving data)</label>
                                <input type="tel" x-model="beneficiary" placeholder="e.g. 0551234567" class="w-full px-4 py-3 rounded-card border border-line focus:border-primary-500 outline-none">
                            </div>
                        </div>
                        <div class="mt-8 flex gap-4">
                            <button type="button" @click="selectedPackage = null" class="flex-1 py-3 rounded-card font-bold border border-line text-muted hover:bg-surface-muted">Cancel</button>
                            <button type="button" @click="checkout()" :disabled="checkingOut" class="flex-1 py-3 rounded-card font-black bg-primary-600 text-white hover:bg-primary-700 disabled:opacity-50">
                                <span x-text="checkingOut ? 'Redirecting…' : 'Pay GHS ' + (selectedPackage ? (selectedPackage.price * 1.03).toFixed(2) : '0')"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function storefrontPage(slug) {
    const networkLogos = JSON.parse(document.querySelector('[data-network-logos]')?.getAttribute('data-network-logos') || '{}');
    const cardBg = JSON.parse(document.querySelector('[data-card-bg]')?.getAttribute('data-card-bg') || '{}');
    function networkKey(network) {
        if (!network) return null;
        const n = String(network).toLowerCase();
        if (n.includes('mtn')) return 'MTN';
        if (n.includes('at') || n.includes('airtel') || n.includes('tigo')) return 'AT';
        if (n.includes('telecel') || n.includes('vodafone')) return 'Telecel';
        return network;
    }
    function networkSlug(network) {
        const key = networkKey(network);
        if (key === 'MTN') return 'mtn';
        if (key === 'AT') return 'at';
        if (key === 'Telecel') return 'telecel';
        return (network || '').toLowerCase();
    }
    return {
        slug,
        packages: [],
        resellerName: '',
        loading: true,
        errorMsg: '',
        successMsg: '',
        selectedPackage: null,
        beneficiary: '',
        checkingOut: false,
        storeWhatsappUrl: '',
        activeNet: 'mtn',
        trackNumber: '',
        trackingOrders: false,
        trackError: '',
        trackOrdersList: null,

        get filteredPackages() {
            if (this.activeNet === 'all') return this.packages;
            return this.packages.filter(p => networkSlug(p.network) === this.activeNet);
        },
        getCardBgClass(network) { return cardBg[networkKey(network)] || cardBg[network] || 'bg-surface-muted0'; },
        getNetworkLogo(network) { return networkLogos[networkKey(network)] || networkLogos[network] || null; },
        isMtn(network) { return networkKey(network) === 'MTN'; },

        async init() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('success') === '1') this.successMsg = params.get('already_processed') ? 'Payment was already processed.' : 'Payment successful! Data will be delivered shortly.';
            if (params.get('error')) this.errorMsg = decodeURIComponent(params.get('error') || 'Payment failed.');
            await this.fetchPackages();
        },

        async fetchPackages() {
            try {
                const res = await fetch('/api/store/' + this.slug + '/packages', { headers: { 'Accept': 'application/json' } });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.errorMsg = data.error || 'Store not found.';
                    this.resellerName = ''; this.packages = []; this.storeWhatsappUrl = '';
                    this.loading = false; return;
                }
                this.resellerName = data.reseller_name || '';
                this.packages = data.packages || [];
                this.storeWhatsappUrl = data.store_whatsapp_url || '';
            } catch (e) {
                this.resellerName = ''; this.packages = []; this.storeWhatsappUrl = '';
            } finally { this.loading = false; }
        },

        selectPackage(pkg) {
            this.selectedPackage = pkg;
            this.beneficiary = '';
            this.errorMsg = '';
        },

        async trackOrders() {
            this.trackError = '';
            this.trackOrdersList = null;
            if (!this.trackNumber.trim()) return;
            this.trackingOrders = true;
            try {
                const res = await fetch('/api/store/' + encodeURIComponent(this.slug) + '/track?number=' + encodeURIComponent(this.trackNumber.trim()), { headers: { 'Accept': 'application/json' } });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.trackError = data.error || 'Could not look up orders.';
                    this.trackOrdersList = [];
                    return;
                }
                this.trackOrdersList = data.orders || [];
            } catch (e) {
                this.trackError = 'Something went wrong. Try again.';
                this.trackOrdersList = [];
            } finally {
                this.trackingOrders = false;
            }
        },

        async checkout() {
            if (!this.selectedPackage || !this.beneficiary.trim()) {
                this.errorMsg = 'Please enter your mobile number.';
                return;
            }
            this.errorMsg = '';
            this.checkingOut = true;
            try {
                const res = await fetch('/api/store/' + this.slug + '/checkout', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ package_id: this.selectedPackage.id, beneficiary: this.beneficiary.trim() })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.errorMsg = data.error || data.message || 'Could not start payment.';
                    this.checkingOut = false; return;
                }
                if (data.authorization_url) { window.location.href = data.authorization_url; return; }
                this.errorMsg = 'No payment link received.';
            } catch (e) { this.errorMsg = 'Something went wrong. Try again.'; }
            this.checkingOut = false;
        }
    };
}
</script>
@endsection
