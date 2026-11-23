@extends('layouts.app')

@section('content')
@section('title', 'Buy Data')
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
@php
    $networkLogos = [
        'MTN' => asset('assets/mtn.jpg'),
        'AT' => asset('assets/airtelTigo.png'),
        'AirtelTigo' => asset('assets/airtelTigo.png'),
        'Telecel' => asset('assets/telecel.png'),
    ];
    $cardBgClasses = ['MTN' => 'bg-mtn-base', 'AT' => 'bg-airteltigo-base', 'AirtelTigo' => 'bg-airteltigo-base', 'Telecel' => 'bg-telecel-base'];
@endphp
<style>
.bundles-page { font-family: 'Outfit', sans-serif; }
.bundles-page .filter-pill { background: #f1f5f9; border: 1px solid #e2e8f0; color: #64748b; font-weight: 600; transition: all 0.2s; }
.bundles-page .filter-pill:hover { background: #e2e8f0; color: #0f172a; }
.bundles-page .filter-pill.active-all { background: #0891b2; border-color: #0891b2; color: white; }
.bundles-page .card-mtn { border-left-color: #eab308; }
.bundles-page .card-at { border-left-color: #a855f7; }
.bundles-page .card-telecel { border-left-color: #ef4444; }
</style>
<div class="bundles-page bg-gray-50 min-h-screen py-8 md:py-12" 
     x-data="bundlesPage()"
     x-init="init()"
     data-network-logos='@json($networkLogos)'
     data-card-bg='@json($cardBgClasses)'>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <header class="text-center mb-10">
            <p class="text-gray-500 text-sm font-semibold uppercase tracking-wider mb-1">Buy Data</p>
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900">Choose your bundle</h1>
            <p class="mt-2 text-gray-500 font-medium">Affordable data for MTN, AirtelTigo & Telecel.</p>
            
            <template x-if="!$store.auth.token && packages.length === 0">
                <p class="mt-4 text-amber-700 font-semibold text-sm">Log in to see prices and buy data.</p>
            </template>

            <div class="mt-8 flex flex-wrap justify-center gap-2">
                <button @click="activeNet = 'all'" :class="activeNet === 'all' ? 'filter-pill active-all' : 'filter-pill'" class="px-5 py-2.5 rounded-xl text-sm">All</button>
                <button @click="activeNet = 'mtn'" :class="activeNet === 'mtn' ? 'bg-[#eab308] text-gray-900 border-[#eab308]' : 'filter-pill'" class="px-5 py-2.5 rounded-xl text-sm font-semibold border transition-all">MTN</button>
                <button @click="activeNet = 'at'" :class="activeNet === 'at' ? 'bg-[#a855f7] text-white border-[#a855f7]' : 'filter-pill'" class="px-5 py-2.5 rounded-xl text-sm font-semibold border transition-all">AirtelTigo</button>
                <button @click="activeNet = 'telecel'" :class="activeNet === 'telecel' ? 'bg-[#ef4444] text-white border-[#ef4444]' : 'filter-pill'" class="px-5 py-2.5 rounded-xl text-sm font-semibold border transition-all">Telecel</button>
            </div>
        </header>

        <template x-if="loading">
            <div class="text-center py-16">
                <p class="text-gray-500 font-semibold">Loading packages...</p>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6" x-show="!loading">
            <template x-for="pkg in filteredPackages" :key="pkg.id">
                <div class="bg-white rounded-xl border border-slate-200 border-l-4 shadow-sm overflow-hidden transition-all hover:shadow-md hover:border-slate-300"
                     :class="{ 'card-mtn': networkSlug(pkg.network) === 'mtn', 'card-at': networkSlug(pkg.network) === 'at', 'card-telecel': networkSlug(pkg.network) === 'telecel' }">
                    <div class="p-5 flex flex-col h-full">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="w-11 h-11 rounded-xl overflow-hidden bg-slate-100 flex items-center justify-center shrink-0">
                                <img x-show="getNetworkLogo(pkg.network)" :src="getNetworkLogo(pkg.network)" :alt="pkg.network" class="w-full h-full object-contain p-1">
                                <span x-show="!getNetworkLogo(pkg.network)" class="text-sm font-bold text-slate-500" x-text="(pkg.network || '').charAt(0)"></span>
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 bg-gray-100 px-2.5 py-1 rounded-lg" x-text="pkg.volume_gb || pkg.network"></span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-0.5" x-text="pkg.plan_name || (pkg.volume_gb + ' – ₵' + pkg.price)"></h3>
                        <p class="text-sm text-gray-500 mb-4" x-text="pkg.network"></p>
                        <div class="mt-auto pt-4 flex items-center justify-between border-t border-slate-100">
                            <span class="text-xl font-bold text-gray-900">GH₵ <span x-text="parseFloat(pkg.price).toFixed(2)"></span></span>
                            <button @click="selectPackage(pkg)" class="px-4 py-2 rounded-lg bg-cyan-500 text-white text-sm font-semibold hover:bg-cyan-600 transition-colors active:scale-95">Select</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <template x-if="!loading && filteredPackages.length === 0">
            <div class="text-center py-16 rounded-xl bg-white border border-slate-200">
                <p class="text-gray-500 font-semibold mb-2">No packages available right now.</p>
                <p x-show="!$store.auth.token" class="text-gray-400 text-sm">Log in to see packages and buy data.</p>
            </div>
        </template>
    </div>

    <!-- Order Modal -->
    <div x-show="showOrderModal" 
         x-cloak
         class="fixed inset-0 z-[150] overflow-y-auto"
         role="dialog"
         aria-modal="true">
        <div x-show="showOrderModal" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             @click="showOrderModal = false"
             class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>

        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div x-show="showOrderModal" 
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="relative bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden border border-slate-200">
                
                <div class="p-6 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <img :src="selectedPackage.logo" class="h-10 w-10 rounded-lg object-contain bg-slate-100 p-1 shrink-0" x-show="selectedPackage.logo" alt="">
                        <div class="min-w-0">
                            <h3 class="text-lg font-bold text-gray-900 truncate" x-text="selectedPackage.plan_name || selectedPackage.name"></h3>
                            <p class="text-sm text-gray-500">GH₵ <span x-text="selectedPackage.price ? parseFloat(selectedPackage.price).toFixed(2) : ''"></span></p>
                        </div>
                    </div>
                    <button @click="showOrderModal = false" class="p-2 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-slate-100 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6">
                    <template x-if="!$store.auth.token">
                        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                            <p class="text-amber-800 font-semibold text-sm">Please log in to purchase.</p>
                            <a href="/login" class="inline-block mt-2 text-cyan-600 font-semibold hover:underline">Log in →</a>
                        </div>
                    </template>
                    <template x-if="$store.auth.token">
                        <div>
                            <div class="flex p-1 bg-slate-100 rounded-xl mb-6">
                                <button @click="orderType = 'single'" :class="orderType === 'single' ? 'bg-white text-gray-900 shadow-sm border border-slate-200' : 'text-gray-500'" class="flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all">Single</button>
                                <button @click="orderType = 'bulk'" :class="orderType === 'bulk' ? 'bg-white text-gray-900 shadow-sm border border-slate-200' : 'text-gray-500'" class="flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all">Bulk</button>
                            </div>

                            <div x-show="orderType === 'single'" class="mb-6">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Mobile number</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                    </div>
                                    <input type="tel" x-model="beneficiary" placeholder="0551234567" class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-cyan-500 text-gray-900 placeholder-gray-400 font-medium">
                                </div>
                            </div>

                            <div x-show="orderType === 'bulk'" class="mb-6">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Numbers (one per line or comma-separated)</label>
                                <textarea x-model="bulkBeneficiaries" rows="4" placeholder="0551234567&#10;0249876543" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-cyan-500 text-gray-900 placeholder-gray-400 font-medium resize-none"></textarea>
                            </div>

                            <button @click="submitOrder()" :disabled="submitting" class="w-full py-3.5 rounded-xl bg-cyan-500 text-white font-semibold hover:bg-cyan-600 disabled:opacity-50 transition-colors">
                                <span x-text="submitting ? 'Processing...' : 'Place order'"></span>
                            </button>
                        </div>
                    </template>
                    
                    <button @click="showOrderModal = false" class="w-full mt-3 py-2 text-gray-500 text-sm font-medium hover:text-gray-700">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function bundlesPage() {
    const networkLogos = JSON.parse(document.querySelector('[data-network-logos]')?.getAttribute('data-network-logos') || '{}');
    const cardBg = JSON.parse(document.querySelector('[data-card-bg]')?.getAttribute('data-card-bg') || '{}');
    return {
        packages: [],
        activeNet: 'all',
        showOrderModal: false,
        orderType: 'single',
        beneficiary: '',
        bulkBeneficiaries: '',
        selectedPackage: { id: null, plan_name: '', price: '', network: '', logo: '' },
        loading: true,
        submitting: false,

        networkKey(network) {
            if (!network) return null;
            const n = String(network).toLowerCase();
            if (n.includes('mtn')) return 'MTN';
            if (n.includes('at') || n.includes('airtel') || n.includes('tigo')) return 'AT';
            if (n.includes('telecel') || n.includes('vodafone')) return 'Telecel';
            return network;
        },
        getCardBgClass(network) {
            const key = this.networkKey(network);
            return cardBg[key] || cardBg[network] || 'bg-slate-500';
        },
        getNetworkLogo(network) {
            const key = this.networkKey(network);
            return networkLogos[key] || networkLogos[network] || null;
        },
        isMtn(network) {
            const key = this.networkKey(network);
            return key === 'MTN';
        },
        networkSlug(network) {
            const key = this.networkKey(network);
            if (key === 'MTN') return 'mtn';
            if (key === 'AT') return 'at';
            if (key === 'Telecel') return 'telecel';
            return (network || '').toLowerCase();
        },

        get filteredPackages() {
            if (this.activeNet === 'all') return this.packages;
            return this.packages.filter(p => this.networkSlug(p.network) === this.activeNet);
        },

        init() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('network')) this.activeNet = urlParams.get('network');
            if (this.$store.auth.token) this.fetchPackages();
            else this.loading = false;
        },

        async fetchPackages() {
            this.loading = true;
            try {
                const response = await fetch('/api/auth/buy-data', {
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' }
                });
                if (response.ok) {
                    const data = await response.json();
                    let list = data.packages && data.packages.length ? data.packages : [];
                    if (list.length === 0 && data.packages_by_network) {
                        const flat = [];
                        for (const net in data.packages_by_network) {
                            (data.packages_by_network[net] || []).forEach(p => flat.push({ ...p, network: p.network || net }));
                        }
                        list = flat;
                    }
                    // Exclude Express(MTN) and any Express(*) network on the client too (e.g. All Bundles tab)
                    this.packages = list.filter(p => {
                        const n = (p.network || '').trim().toLowerCase();
                        return n !== 'express(mtn)' && !n.startsWith('express(');
                    });
                }
            } catch (e) {
                console.error('Fetch packages failed', e);
            } finally {
                this.loading = false;
            }
        },

        selectPackage(pkg) {
            this.selectedPackage = {
                id: pkg.id,
                plan_name: pkg.plan_name,
                price: pkg.price,
                network: pkg.network,
                logo: this.getNetworkLogo(pkg.network)
            };
            this.beneficiary = '';
            this.bulkBeneficiaries = '';
            this.showOrderModal = true;
        },

        async submitOrder() {
            if (!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            if (!this.selectedPackage.id) {
                alert('Please select a package.');
                return;
            }
            if (this.orderType === 'single') {
                const num = (this.beneficiary || '').replace(/\s/g, '');
                if (!num || num.length < 10) {
                    alert('Enter a valid Ghana mobile number (e.g. 0551234567).');
                    return;
                }
            } else {
                const lines = (this.bulkBeneficiaries || '').split(/[\n,]+/).map(s => s.replace(/\s/g, '')).filter(s => s.length >= 10);
                if (lines.length === 0) {
                    alert('Enter at least one valid number per line or comma-separated.');
                    return;
                }
            }

            this.submitting = true;
            try {
                const url = this.orderType === 'single' ? '/api/auth/buy-data' : '/api/auth/buy-data/bulk';
                const body = this.orderType === 'single'
                    ? { package_id: this.selectedPackage.id, beneficiary: (this.beneficiary || '').replace(/\s/g, '') }
                    : { package_id: this.selectedPackage.id, beneficiaries: (this.bulkBeneficiaries || '').split(/[\n,]+/).map(s => s.replace(/\s/g, '')).filter(s => s.length >= 10).join('\n') };
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(body)
                });
                const result = await response.json().catch(() => ({}));
                if (response.ok && !result.errors) {
                    this.showOrderModal = false;
                    const msg = result.message || (result.sent_to_api ? 'Data sent.' : 'Order received.');
                    window.location.href = '/orders?success=' + encodeURIComponent(msg);
                    return;
                }
                const err = (result.errors && result.errors.beneficiary && result.errors.beneficiary[0]) || (result.errors && result.errors.beneficiaries && result.errors.beneficiaries[0]) || result.message || 'Order failed. Try again.';
                alert(err);
            } catch (e) {
                console.error(e);
                alert('Request failed. Try again.');
            } finally {
                this.submitting = false;
            }
        }
    };
}
</script>
@endsection
