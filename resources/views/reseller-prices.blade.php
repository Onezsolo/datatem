<x-layouts.member title="Reseller prices">
<div class="min-h-screen bg-surface-muted py-12" x-data="resellerPricesPage()" x-init="init()">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="/reseller" class="text-primary-600 font-bold hover:underline mb-6 block flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
            Back to Reseller
        </a>

        <div class="mb-12">
            <h1 class="text-4xl font-black text-brand-navy tracking-tight">Set your <span class="text-brand-navy">prices</span></h1>
            <p class="mt-2 text-muted font-medium">Add your margin on top of the base (admin) price. Your storefront will show these prices to walking customers.</p>
        </div>

        <div x-show="errorMsg" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-card font-bold" x-text="errorMsg"></div>
        <div x-show="successMsg" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-card font-bold" x-text="successMsg"></div>

        <div x-show="!isReseller" class="bg-surface rounded-card shadow-none border border-line p-10 text-center">
            <p class="text-muted font-medium">You must be an approved reseller to set prices. <a href="/reseller" class="text-primary-600 font-bold hover:underline">Go to Reseller page</a>.</p>
        </div>

        <div x-show="isReseller && storeUrl" class="mb-8 p-6 bg-primary-50 border border-primary-100 rounded-card">
            <p class="text-sm font-bold text-primary-800">Your storefront link (share with walking customers):</p>
            <a :href="storeUrl" target="_blank" class="text-lg font-black text-primary-600 break-all hover:underline" x-text="storeUrl"></a>
        </div>

        <div x-show="isReseller" class="glass rounded-[40px] shadow-none border border-line/50 overflow-hidden">
            <div class="p-6 border-b border-line bg-surface-muted/50">
                <p class="text-sm font-bold text-muted mb-6">Base price = admin price. Set your selling price (must be ≥ base). Your profit = selling price − base.</p>
                <div class="flex flex-wrap gap-2 p-1.5 bg-surface-muted rounded-card w-fit border border-line">
                    <button @click="activeNet = 'mtn'" :class="activeNet === 'mtn' ? 'bg-[#F0C232] text-brand-navy shadow-none' : 'text-muted'" class="px-6 py-2.5 rounded-card text-sm font-bold transition-all">MTN</button>
                    <button @click="activeNet = 'at'" :class="activeNet === 'at' ? 'bg-[#8b5cf6] text-white shadow-none' : 'text-muted'" class="px-6 py-2.5 rounded-card text-sm font-bold transition-all">AirtelTigo</button>
                    <button @click="activeNet = 'telecel'" :class="activeNet === 'telecel' ? 'bg-[#E0505B] text-white shadow-none' : 'text-muted'" class="px-6 py-2.5 rounded-card text-sm font-bold transition-all">Telecel</button>
                    <button @click="activeNet = 'all'" :class="activeNet === 'all' ? 'bg-surface text-brand-navy shadow-none' : 'text-muted'" class="px-6 py-2.5 rounded-card text-sm font-bold transition-all">All</button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-surface-muted border-b border-line">
                        <tr>
                            <th class="px-6 py-4 text-xs font-black text-muted uppercase tracking-widest">Package</th>
                            <th class="px-6 py-4 text-xs font-black text-muted uppercase tracking-widest text-right">Base (GHS)</th>
                            <th class="px-6 py-4 text-xs font-black text-muted uppercase tracking-widest text-right">Your price (GHS)</th>
                            <th class="px-6 py-4 text-xs font-black text-muted uppercase tracking-widest text-right">Your profit</th>
                            <th class="px-6 py-4 text-xs font-black text-muted uppercase tracking-widest text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <template x-for="pkg in filteredPackages" :key="pkg.id">
                            <tr class="hover:bg-surface-muted/80">
                                <td class="px-6 py-4">
                                    <span class="font-black text-brand-navy" x-text="pkg.network"></span>
                                    <span class="text-muted font-medium" x-text="' ' + pkg.volume_gb"></span>
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-muted" x-text="pkg.base_price.toFixed(2)"></td>
                                <td class="px-6 py-4 text-right">
                                    <input type="number" :value="pkg.selling_price" step="0.01" min="0"
                                           @input="pkg._edit = parseFloat($event.target.value) || pkg.base_price"
                                           class="w-24 px-3 py-2 rounded-card border border-line text-right font-black text-brand-navy">
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-green-600" x-text="((pkg._edit !== undefined ? pkg._edit : pkg.selling_price) - pkg.base_price).toFixed(2)"></td>
                                <td class="px-6 py-4 text-right">
                                    <button type="button"
                                            @click="savePrice(pkg)"
                                            :disabled="savingId === pkg.id"
                                            class="px-4 py-2 rounded-card font-bold bg-primary-600 text-white hover:bg-primary-700 disabled:opacity-50 text-sm">
                                        <span x-text="savingId === pkg.id ? 'Saving…' : 'Save'"></span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div x-show="filteredPackages.length === 0" class="p-8 text-center text-muted font-bold">No packages in this category.</div>
        </div>
    </div>
</div>

<script>
function resellerPricesPage() {
    function networkSlug(network) {
        if (!network) return '';
        const n = String(network).toLowerCase();
        if (n.includes('mtn')) return 'mtn';
        if (n.includes('at') || n.includes('airtel') || n.includes('tigo')) return 'at';
        if (n.includes('telecel') || n.includes('vodafone')) return 'telecel';
        return n;
    }
    return {
        packages: [],
        storeUrl: '',
        isReseller: false,
        savingId: null,
        errorMsg: '',
        successMsg: '',
        activeNet: 'mtn',

        get filteredPackages() {
            if (this.activeNet === 'all') return this.packages;
            return this.packages.filter(p => networkSlug(p.network) === this.activeNet);
        },

        async init() {
            if (!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            await this.fetchResellerStatus();
            await this.fetchPackages();
        },

        async fetchResellerStatus() {
            try {
                const res = await fetch('/api/auth/reseller', { headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                this.isReseller = data.request && data.request.status === 'approved';
                if (data.store_url) this.storeUrl = data.store_url;
            } catch (e) { console.error(e); }
        },

        async fetchPackages() {
            try {
                const res = await fetch('/api/auth/reseller/packages', { headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' } });
                if (!res.ok) {
                    if (res.status === 403) return;
                    this.errorMsg = 'Failed to load packages.';
                    return;
                }
                const data = await res.json();
                this.packages = data.packages || [];
            } catch (e) {
                this.errorMsg = 'Failed to load packages.';
            }
        },

        async savePrice(pkg) {
            const price = pkg._edit !== undefined ? pkg._edit : pkg.selling_price;
            if (price < pkg.base_price) {
                this.errorMsg = 'Selling price must be at least ' + pkg.base_price.toFixed(2) + ' GHS.';
                return;
            }
            this.errorMsg = '';
            this.successMsg = '';
            this.savingId = pkg.id;
            try {
                const res = await fetch('/api/auth/reseller/packages/' + pkg.id + '/price', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ selling_price: price })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.errorMsg = data.error || data.message || 'Failed to save.';
                    return;
                }
                pkg.selling_price = data.selling_price;
                pkg.your_profit = data.your_profit;
                delete pkg._edit;
                this.successMsg = 'Price saved.';
                setTimeout(() => { this.successMsg = ''; }, 3000);
            } catch (e) {
                this.errorMsg = 'Failed to save.';
            } finally {
                this.savingId = null;
            }
        }
    };
}
</script>
</x-layouts.member>
