<x-layouts.member title="Reseller">
@php
    $networkLogos = [
        'MTN' => asset('assets/mtn.jpg'),
        'AT' => asset('assets/airtelTigo.png'),
        'AirtelTigo' => asset('assets/airtelTigo.png'),
        'Telecel' => asset('assets/telecel.png'),
    ];
    $cardBgClasses = ['MTN' => 'bg-mtn', 'AT' => 'bg-airteltigo', 'AirtelTigo' => 'bg-airteltigo', 'Telecel' => 'bg-telecel'];
@endphp
<style>
.reseller-page { font-family: inherit; }
.reseller-page .filter-pill { background: #f1f5f9; border: 1px solid #e2e8f0; color: #64748b; font-weight: 600; transition: all 0.2s; }
.reseller-page .filter-pill:hover { background: #e2e8f0; color: #0f172a; }
.reseller-page .filter-pill.active { background: var(--color-brand-navy); border-color: var(--color-brand-navy); color: white; }
.reseller-page .card-mtn { border-left-color: #eab308; }
.reseller-page .card-at { border-left-color: #a855f7; }
.reseller-page .card-telecel { border-left-color: #ef4444; }
</style>
<div class="reseller-page min-h-screen bg-gray-50 py-8 md:py-12" x-data="resellerPage()" x-init="init()"
     data-network-logos='@json($networkLogos)'
     data-card-bg='@json($cardBgClasses)'>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="/dashboard" class="text-brand-navy font-semibold hover:text-brand-navy-soft mb-6 inline-flex items-center text-sm">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Dashboard
        </a>

        <header class="mb-8">
            <p class="text-gray-500 text-sm font-semibold uppercase tracking-wider mb-1">Reseller</p>
            <template x-if="request && request.status === 'approved'">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Your reseller store</h1>
                    <p class="mt-1 text-gray-500 font-medium">Manage your store, prices, and sales.</p>
                </div>
            </template>
            <template x-if="!request || request.status !== 'approved'">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Become a reseller</h1>
                    <p class="mt-1 text-gray-500 font-medium">Request access to sell data bundles from your own store.</p>
                </div>
            </template>
        </header>

        <div x-show="errorMsg" x-transition class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl font-semibold text-sm" x-text="errorMsg"></div>
        <div x-show="successMsg" x-transition class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl font-semibold text-sm" x-text="successMsg"></div>

        <!-- NOT APPROVED -->
        <div x-show="!request || request.status !== 'approved'" class="space-y-6">
            <div x-show="request" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-6">
                    <h2 class="text-base font-bold text-gray-900 mb-3">Your request</h2>
                    <div class="flex items-center gap-3 mb-3">
                        <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold uppercase"
                              :class="{
                                  'bg-amber-100 text-amber-700': request && request.status === 'pending',
                                  'bg-emerald-100 text-emerald-700': request && request.status === 'approved',
                                  'bg-red-100 text-red-700': request && request.status === 'rejected'
                              }"
                              x-text="request ? request.status : ''"></span>
                        <span class="text-xs text-gray-500" x-text="request ? 'Submitted ' + formatDate(request.created_at) : ''"></span>
                    </div>
                    <p x-show="request && request.message" class="text-gray-600 text-sm mb-3" x-text="request.message"></p>
                    <p x-show="request && request.admin_notes" class="p-3 bg-slate-50 rounded-lg text-sm text-gray-600 border border-slate-100" x-text="'Admin: ' + request.admin_notes"></p>
                </div>
            </div>

            <div x-show="canSubmitRequest" class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-1" x-text="request && request.status === 'rejected' ? 'Submit a new request' : 'Request reseller status'"></h2>
                <p class="text-gray-500 text-sm mb-4">Tell us why you want to become a reseller (optional). We'll review shortly.</p>
                <textarea x-model="message" rows="3" placeholder="e.g. I run a small shop and would like to sell data bundles..."
                          class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-brand-navy font-medium text-gray-900 resize-none text-sm"></textarea>
                <button type="button" @click="submitRequest()" :disabled="submitting"
                        class="mt-4 px-6 py-3 rounded-xl font-semibold bg-brand-navy text-white hover:bg-brand-navy disabled:opacity-50 transition-colors text-sm">
                    <span x-show="!submitting">Submit request</span>
                    <span x-show="submitting">Submitting…</span>
                </button>
            </div>

            <div x-show="request && request.status === 'pending'" class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center">
                <div class="w-14 h-14 bg-amber-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900 mb-1">Under review</h2>
                <p class="text-gray-500 text-sm">We'll notify you once your request has been reviewed.</p>
            </div>

            <div x-show="request && request.status === 'rejected'" class="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center">
                <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h2 class="text-lg font-bold text-gray-900 mb-1">Request not approved</h2>
                <p class="text-gray-500 text-sm mb-4">You can submit a new request above or contact support.</p>
                <a href="/support" class="inline-flex items-center px-5 py-2.5 rounded-xl font-semibold bg-slate-100 text-gray-700 hover:bg-surface-muted hover:text-brand-navy-soft hover:border-cyan-200 border border-transparent transition-colors text-sm">Contact support</a>
            </div>
        </div>

        <!-- APPROVED: store setup incomplete -->
        <div x-show="request && request.status === 'approved' && !storeSetupComplete" class="max-w-2xl">
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 md:p-8">
                <div class="flex items-start gap-4 mb-6">
                    <div class="w-12 h-12 rounded-xl bg-amber-200 text-amber-800 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 mb-1">Your store isn’t live yet</h2>
                        <p class="text-amber-800 text-sm font-medium mb-1">Complete the 3 steps below. Until then, your store link will show "This store is not set up yet."</p>
                        <p class="text-gray-600 text-xs">Takes less than a minute — then share your link and set prices.</p>
                    </div>
                </div>
                <div class="space-y-5">
                    <div class="flex gap-3">
                        <span class="w-7 h-7 rounded-lg bg-amber-200 text-amber-800 font-bold text-sm flex items-center justify-center shrink-0">1</span>
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Store URL (e.g. iksoft)</label>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-gray-500 text-sm shrink-0">{{ url('/store') }}/</span>
                                <input type="text" x-model="storeSlug" placeholder="iksoft"
                                       class="flex-1 min-w-[120px] px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-navy font-medium text-gray-900 bg-white text-sm"
                                       @input="storeSlug = $event.target.value.toLowerCase().replace(/[^a-z0-9-]/g, '')">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Letters, numbers and hyphens only.</p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <span class="w-7 h-7 rounded-lg bg-amber-200 text-amber-800 font-bold text-sm flex items-center justify-center shrink-0">2</span>
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Store name</label>
                            <input type="text" x-model="storeName" placeholder="e.g. IkSoft Data" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-navy font-medium text-gray-900 bg-white text-sm">
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <span class="w-7 h-7 rounded-lg bg-amber-200 text-amber-800 font-bold text-sm flex items-center justify-center shrink-0">3</span>
                        <div class="flex-1">
                            <label class="block text-xs font-semibold text-gray-600 mb-1">WhatsApp (for customer support)</label>
                            <input type="text" x-model="storeWhatsapp" placeholder="e.g. 0551234567" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:outline-none focus:border-brand-navy font-medium text-gray-900 bg-white text-sm">
                        </div>
                    </div>
                    <div class="pt-2">
                        <button type="button" @click="saveStoreSetup()" :disabled="savingSetup"
                                class="px-6 py-3 rounded-xl font-semibold bg-brand-navy text-white hover:bg-brand-navy disabled:opacity-50 transition-colors text-sm">
                            <span x-text="savingSetup ? 'Saving…' : 'Save and go live'"></span>
                        </button>
                        <p class="text-xs text-gray-500 mt-2">Then your store link will work and you can set prices.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- APPROVED + SETUP DONE -->
        <div x-show="request && request.status === 'approved' && storeSetupComplete" class="space-y-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total sales</p>
                        <p class="text-xl font-bold text-gray-900">GH₵ <span x-text="totalSales.toFixed(2)"></span></p>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total profit</p>
                        <p class="text-xl font-bold text-emerald-600">GH₵ <span x-text="totalProfit.toFixed(2)"></span></p>
                    </div>
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 col-span-2 sm:col-span-1">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Wallet (buy data)</p>
                        <p class="text-xl font-bold text-brand-navy-soft">GH₵ <span x-text="typeof walletBalance === 'number' ? walletBalance.toFixed(2) : '0.00'"></span></p>
                        <p class="text-xs font-semibold text-emerald-600 mt-2">Withdrawable profit: GH₵ <span x-text="typeof withdrawableBalance === 'number' ? withdrawableBalance.toFixed(2) : '0.00'"></span></p>
                        <a href="/reseller/withdrawals" class="text-xs font-semibold text-brand-navy hover:underline mt-2 inline-block">Withdraw earnings →</a>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="/reseller/prices" class="inline-flex items-center px-5 py-2.5 rounded-xl font-semibold bg-brand-navy text-white hover:bg-brand-navy transition-colors text-sm">Set prices</a>
                    <a href="/reseller/withdrawals" class="inline-flex items-center px-5 py-2.5 rounded-xl font-semibold bg-white border border-slate-200 text-gray-700 hover:bg-slate-50 hover:border-emerald-200 hover:text-emerald-700 transition-colors text-sm">Withdraw earnings</a>
                    <a :href="storeUrl" target="_blank" class="inline-flex items-center px-5 py-2.5 rounded-xl font-semibold bg-white border border-slate-200 text-gray-700 hover:bg-slate-50 hover:border-cyan-200 hover:text-brand-navy-soft transition-colors text-sm">Open store</a>
                </div>
            </div>

            <div class="p-4 bg-surface-muted border border-cyan-200 rounded-xl">
                <p class="text-xs font-semibold text-brand-navy-soft uppercase tracking-wider mb-1">Your store link — share with customers</p>
                <a :href="storeUrl" target="_blank" class="text-base font-bold text-brand-navy break-all hover:underline" x-text="storeUrl"></a>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-200">
                    <h2 class="text-lg font-bold text-gray-900">Recent sales</h2>
                    <p class="text-gray-500 text-sm mt-0.5">Your profit per purchase is shown below.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left min-w-[600px]">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Reference</th>
                                <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Beneficiary</th>
                                <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">You gained</th>
                                <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="sale in recentSales" :key="sale.id">
                                <tr class="border-b border-slate-100 hover:bg-slate-50/80">
                                    <td class="px-5 py-3 text-sm font-medium text-gray-600" x-text="sale.created_at ? new Date(sale.created_at).toLocaleString(undefined, { dateStyle: 'short', timeStyle: 'short', hour12: true }) : '—'"></td>
                                    <td class="px-5 py-3 font-mono text-sm font-medium text-gray-700" x-text="sale.reference || '—'"></td>
                                    <td class="px-5 py-3 font-medium text-gray-800" x-text="sale.beneficiary || '—'"></td>
                                    <td class="px-5 py-3 font-medium text-gray-800">GH₵ <span x-text="Number(sale.amount).toFixed(2)"></span></td>
                                    <td class="px-5 py-3 font-bold text-emerald-600">GH₵ <span x-text="Number(sale.reseller_profit).toFixed(2)"></span></td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-semibold"
                                              :class="{
                                                  'bg-emerald-100 text-emerald-700': sale.status === 'delivered' || sale.status === 'processing',
                                                  'bg-amber-100 text-amber-700': sale.status === 'pending',
                                                  'bg-red-100 text-red-700': sale.status === 'failed',
                                                  'bg-slate-100 text-slate-600': !['delivered','processing','pending','failed'].includes(sale.status)
                                              }"
                                              x-text="sale.status || '—'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div x-show="recentSales.length === 0" class="p-10 text-center text-gray-500 font-medium text-sm">No sales yet. Share your store link to start selling.</div>
            </div>

            <!-- Your store packages -->
            <div>
                <h2 class="text-lg font-bold text-gray-900 mb-1">Your store packages</h2>
                <p class="text-gray-500 text-sm mb-4">Bundles customers see. Set your price on each to add profit.</p>

                <div class="flex flex-wrap gap-2 mb-6">
                    <button @click="activeNet = 'all'" :class="activeNet === 'all' ? 'filter-pill active' : 'filter-pill'" class="px-5 py-2.5 rounded-xl text-sm">All</button>
                    <button @click="activeNet = 'mtn'" :class="activeNet === 'mtn' ? 'bg-[#eab308] text-gray-900 border-[#eab308]' : 'filter-pill'" class="px-5 py-2.5 rounded-xl text-sm font-semibold border transition-all">MTN</button>
                    <button @click="activeNet = 'at'" :class="activeNet === 'at' ? 'bg-[#a855f7] text-white border-[#a855f7]' : 'filter-pill'" class="px-5 py-2.5 rounded-xl text-sm font-semibold border transition-all">AirtelTigo</button>
                    <button @click="activeNet = 'telecel'" :class="activeNet === 'telecel' ? 'bg-[#ef4444] text-white border-[#ef4444]' : 'filter-pill'" class="px-5 py-2.5 rounded-xl text-sm font-semibold border transition-all">Telecel</button>
                </div>

                <div x-show="packagesLoading" class="text-center py-10">
                    <p class="text-gray-500 font-medium text-sm">Loading packages...</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" x-show="!packagesLoading && filteredPackages.length">
                    <template x-for="pkg in filteredPackages" :key="pkg.id">
                        <div class="bg-white rounded-xl border border-slate-200 border-l-4 shadow-sm overflow-hidden transition-all hover:shadow-md"
                             :class="{ 'card-mtn': networkSlug(pkg.network) === 'mtn', 'card-at': networkSlug(pkg.network) === 'at', 'card-telecel': networkSlug(pkg.network) === 'telecel' }">
                            <div class="p-5 flex flex-col h-full">
                                <div class="flex items-start justify-between gap-2 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center p-1 shrink-0">
                                        <img x-show="getNetworkLogo(pkg.network)" :src="getNetworkLogo(pkg.network)" :alt="pkg.network" class="w-full h-full object-contain">
                                        <span x-show="!getNetworkLogo(pkg.network)" class="text-xs font-bold text-slate-500" x-text="(pkg.network || '').charAt(0)"></span>
                                    </div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500 bg-gray-100 px-2 py-0.5 rounded" x-text="pkg.volume_gb"></span>
                                </div>
                                <h3 class="text-base font-bold text-gray-900 mb-2" x-text="pkg.network + ' ' + pkg.volume_gb"></h3>
                                <div class="text-sm text-gray-500 mb-2">Base: GH₵ <span x-text="pkg.base_price.toFixed(2)"></span></div>
                                <div class="font-bold text-gray-900 mb-1">Your price: GH₵ <span x-text="pkg.selling_price.toFixed(2)"></span></div>
                                <p class="text-xs font-semibold mb-4" :class="pkg.your_profit > 0 ? 'text-emerald-600' : 'text-gray-500'" x-text="pkg.your_profit > 0 ? ('Profit: GH₵ ' + pkg.your_profit.toFixed(2)) : 'Same as base'"></p>
                                <a href="/reseller/prices" class="mt-auto pt-3 border-t border-slate-100 inline-flex items-center justify-center py-2 rounded-lg bg-surface-muted text-brand-navy font-semibold text-sm hover:bg-line transition-colors">Set prices</a>
                            </div>
                        </div>
                    </template>
                </div>

                <div x-show="!packagesLoading && filteredPackages.length === 0" class="text-center py-10 rounded-xl bg-white border border-slate-200">
                    <p class="text-gray-500 font-medium text-sm">No packages available. Sync packages in Admin.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function resellerPage() {
    const networkLogos = JSON.parse(document.querySelector('[data-network-logos]')?.getAttribute('data-network-logos') || '{}');
    const cardBg = JSON.parse(document.querySelector('[data-card-bg]')?.getAttribute('data-card-bg') || '{}');

    return {
        request: null,
        message: '',
        submitting: false,
        errorMsg: '',
        successMsg: '',
        storeUrl: '/store',
        totalSales: 0,
        totalProfit: 0,
        walletBalance: 0,
        withdrawableBalance: 0,
        recentSales: [],
        storeSetupComplete: false,
        storeSlug: '',
        storeName: '',
        storeWhatsapp: '',
        savingSetup: false,
        packages: [],
        packagesLoading: false,
        activeNet: 'all',

        get canSubmitRequest() {
            if (!this.request) return true;
            if (this.request.status === 'approved') return false;
            return this.request.status === 'rejected';
        },

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
            return this.networkKey(network) === 'MTN';
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

        async init() {
            if (!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            await this.fetchStatus();
        },

        async fetchStatus() {
            try {
                const res = await fetch('/api/auth/reseller', {
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' }
                });
                if (!res.ok) return;
                const data = await res.json();
                this.request = data.request || null;
                if (data.store_url) this.storeUrl = data.store_url;
                if (typeof data.total_sales === 'number') this.totalSales = data.total_sales;
                if (typeof data.total_profit === 'number') this.totalProfit = data.total_profit;
                if (typeof data.wallet_balance === 'number') this.walletBalance = data.wallet_balance;
                if (typeof data.reseller_withdrawable_balance === 'number') {
                    this.withdrawableBalance = data.reseller_withdrawable_balance;
                }
                this.recentSales = Array.isArray(data.recent_sales) ? data.recent_sales : [];
                this.storeSetupComplete = data.store_setup_complete === true;
                if (data.store_slug) this.storeSlug = data.store_slug;
                if (data.store_name) this.storeName = data.store_name;
                if (data.store_whatsapp) this.storeWhatsapp = data.store_whatsapp;
                if (this.request && this.request.status === 'approved' && this.storeSetupComplete) {
                    this.fetchPackages();
                }
            } catch (e) {
                console.error(e);
            }
        },

        async fetchPackages() {
            if (!this.storeSetupComplete) return;
            this.packagesLoading = true;
            try {
                const res = await fetch('/api/auth/reseller/packages', {
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.packages = data.packages || [];
                }
            } catch (e) {
                this.packages = [];
            } finally {
                this.packagesLoading = false;
            }
        },

        formatDate(iso) {
            if (!iso) return '';
            return new Date(iso).toLocaleDateString(undefined, { dateStyle: 'medium' });
        },

        async saveStoreSetup() {
            this.errorMsg = '';
            this.successMsg = '';
            const slug = (this.storeSlug || '').toLowerCase().replace(/[^a-z0-9-]/g, '');
            if (!slug) {
                this.errorMsg = 'Please enter a store URL (e.g. iksoft).';
                return;
            }
            if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)) {
                this.errorMsg = 'Store URL can only contain lowercase letters, numbers and hyphens.';
                return;
            }
            if (!this.storeName.trim()) {
                this.errorMsg = 'Please enter a store name.';
                return;
            }
            if (!this.storeWhatsapp.trim()) {
                this.errorMsg = 'Please enter a WhatsApp number for complaints.';
                return;
            }
            this.savingSetup = true;
            try {
                const res = await fetch('/api/auth/reseller/store-setup', {
                    method: 'PUT',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        store_slug: slug,
                        store_name: this.storeName.trim(),
                        store_whatsapp: this.storeWhatsapp.trim()
                    })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.errorMsg = (data.errors && data.errors.store_slug && data.errors.store_slug[0]) || data.error || data.message || 'Failed to save.';
                    this.savingSetup = false;
                    return;
                }
                this.storeSetupComplete = true;
                if (data.store_url) this.storeUrl = data.store_url;
                if (data.store_slug) this.storeSlug = data.store_slug;
                this.successMsg = data.message || 'Store is now live. Share your link with customers.';
                await this.fetchPackages();
                setTimeout(() => { this.successMsg = ''; }, 4000);
            } catch (e) {
                this.errorMsg = 'Something went wrong. Try again.';
            }
            this.savingSetup = false;
        },

        async submitRequest() {
            this.errorMsg = '';
            this.successMsg = '';
            this.submitting = true;
            try {
                const res = await fetch('/api/auth/reseller', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ message: this.message || null })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.errorMsg = data.message || (data.errors && data.errors.message ? data.errors.message[0] : 'Failed to submit request.');
                    return;
                }
                this.successMsg = data.message || 'Request submitted.';
                this.message = '';
                await this.fetchStatus();
            } catch (e) {
                this.errorMsg = 'Something went wrong. Try again.';
            } finally {
                this.submitting = false;
            }
        }
    };
}
</script>
</x-layouts.member>
