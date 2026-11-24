<x-layouts.admin title="Bundles">
@php
    $networkLogos = [
        'MTN' => asset('assets/mtn.jpg'),
        'AT' => asset('assets/airtelTigo.png'),
        'AirtelTigo' => asset('assets/airtelTigo.png'),
        'Telecel' => asset('assets/telecel.png'),
    ];
@endphp
<div class="admin-page min-h-screen bg-surface-muted py-8"
     data-network-logos='@json($networkLogos)' 
     x-data="packageManagement()" 
     x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <a href="/admin/dashboard" class="admin-back mb-2 inline-flex items-center text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to Dashboard
                </a>
                <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-1">Inventory</p>
                <h1 class="text-3xl font-bold text-brand-navy tracking-tight">Packages</h1>
                <p class="text-muted mt-1 text-sm">Manage network costs and profit margins. Synced via API.</p>
            </div>
            <button @click="syncPackages()" :disabled="syncing"
                    class="btn-admin-primary px-5 py-2.5 flex items-center gap-2 text-sm shrink-0 disabled:opacity-50">
                <svg class="w-5 h-5" :class="syncing && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span x-text="syncing ? 'Syncing...' : 'Sync with API'"></span>
            </button>
        </div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div class="flex flex-wrap gap-2 p-1 bg-surface-muted rounded-card w-fit border border-line">
                <button @click="activeTab = 'all'" :class="activeTab === 'all' ? 'bg-brand-navy text-white shadow-none' : 'text-muted hover:bg-surface'" class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all">All</button>
                <template x-for="net in networks" :key="net">
                    <button @click="activeTab = net" :class="activeTab === net ? (getTabClass(net) || 'bg-brand-navy text-white') : 'text-muted hover:bg-surface'" class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all" x-text="net"></button>
                </template>
            </div>
            <div class="relative w-full md:max-w-xs">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" placeholder="Search packages..." class="w-full pl-12 pr-4 py-3 rounded-card bg-surface border border-line focus:border-brand-navy outline-none text-brand-navy text-sm">
            </div>
        </div>

        <!-- Package Cards Grid – same style as dashboard: white outer, network-colored inner, form in white panel -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <template x-for="pkg in filteredPackages" :key="pkg.id">
                <div x-show="activeTab === 'all' || pkg.network === activeTab" 
                     class="group relative admin-card p-2 transition-all hover:border-line">
                    <!-- Colored header: MTN black text, badge black bg white text, logo rounded white border -->
                    <div class="rounded-t-[24px] p-6 flex flex-col"
                         :class="[getCardBgClass(pkg.network), isMtnCard(pkg.network) ? 'text-brand-navy' : 'text-white']">
                        <div class="flex justify-between items-start mb-4">
                            <div class="h-10 w-10 rounded-full overflow-hidden flex items-center justify-center bg-surface/20 border-2 border-white shadow-none">
                                <img x-show="getNetworkLogo(pkg.network)" :src="getNetworkLogo(pkg.network)" :alt="pkg.network" class="h-full w-full object-contain p-0.5">
                                <span x-show="!getNetworkLogo(pkg.network)" class="text-lg font-black" :class="isMtnCard(pkg.network) ? 'text-brand-navy' : 'text-white/90'" x-text="(pkg.network || '').charAt(0)"></span>
                            </div>
                            <span class="bg-brand-navy text-white px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                                  x-text="pkg.status || 'In Stock'"></span>
                        </div>
                        <h3 class="text-xl font-black mb-1" x-text="pkg.plan_label || (pkg.volume_gb + ' – GHS ' + pkg.display_price)"></h3>
                        <p class="text-sm font-medium italic" :class="isMtnCard(pkg.network) ? 'text-ink' : 'text-white/80'" x-text="pkg.network"></p>
                    </div>
                    <!-- White form panel (admin-only) -->
                    <div class="rounded-b-[24px] bg-surface p-6 border border-t-0 border-line flex flex-col">
                        <div class="space-y-4 flex-grow">
                            <div class="flex justify-between items-center p-4 rounded-card bg-surface-muted/80 border border-line">
                                <span class="text-xs font-black text-muted uppercase tracking-wider">Provider Cost</span>
                                <span class="text-lg font-black text-muted">GHS <span x-text="parseFloat(pkg.console_price || 0).toFixed(2)"></span></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2 rounded-card bg-surface-muted/50 border border-line" x-show="pkg.provider_bundle_id">
                                <span class="text-[10px] font-black text-muted uppercase tracking-wider">Provider bundle ID</span>
                                <span class="text-sm font-mono text-muted" x-text="pkg.provider_bundle_id || '—'"></span>
                            </div>
                            <div class="relative">
                                <label class="absolute left-4 top-1/2 -translate-y-1/2 text-brand-navy font-semibold text-sm">GHS</label>
                                <input type="number" x-model="pkg.selling_price" step="0.01" min="0" class="w-full pl-14 pr-4 py-4 rounded-card border border-line bg-surface focus:border-brand-navy focus:ring-2 focus:ring-brand-navy/20 transition-all text-lg font-semibold text-brand-navy outline-none" placeholder="Selling price">
                                <p class="mt-2 text-[10px] font-black text-muted uppercase tracking-widest ml-4">Set Your Selling Price</p>
                            </div>
                        </div>
                        <div class="border-t border-line pt-6 mt-4">
                            <div class="flex justify-between mb-4 px-2">
                                <span class="text-xs font-black text-muted uppercase tracking-wider">Projected Profit</span>
                                <span class="text-lg font-black" :class="(parseFloat(pkg.selling_price) || 0) > parseFloat(pkg.console_price || 0) ? 'text-green-600' : 'text-red-600'" 
                                      x-text="'GHS ' + ((parseFloat(pkg.selling_price) || 0) - parseFloat(pkg.console_price || 0)).toFixed(2)"></span>
                            </div>
                            <button type="button"
                                    @click="savePackage(pkg)"
                                    :disabled="pkg.saving === true"
                                    class="w-full btn-admin-primary py-3 rounded-card flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-text="pkg.saving === true ? 'Saving...' : 'Update Pricing'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <template x-if="loading">
            <div class="text-center py-12">
                <p class="text-muted font-bold">Loading packages...</p>
            </div>
        </template>

        <template x-if="!loading && filteredPackages.length === 0">
            <div class="text-center py-16 admin-card">
                <div class="w-16 h-16 bg-surface-muted text-muted rounded-card flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <h3 class="text-xl font-bold text-brand-navy">No packages found</h3>
                <p class="text-muted text-sm mb-4">Sync with API or adjust search.</p>
                <button @click="syncPackages()" :disabled="syncing" class="btn-admin-primary px-5 py-2.5 text-sm disabled:opacity-50">Sync with API</button>
            </div>
        </template>
    </div>
</div>

<script>
function packageManagement() {
    const networkLogos = JSON.parse(document.querySelector('[data-network-logos]')?.getAttribute('data-network-logos') || '{}');
    const tabClasses = {
        'MTN': 'bg-[#FFCC00] text-brand-navy shadow-none',
        'AT': 'bg-[#9333ea] text-white shadow-none',
        'AirtelTigo': 'bg-[#9333ea] text-white shadow-none',
        'Telecel': 'bg-[#FF0000] text-white shadow-none',
    };
    const cardGradients = {
        'MTN': 'bg-mtn',
        'AT': 'bg-airteltigo',
        'AirtelTigo': 'bg-airteltigo',
        'Telecel': 'bg-telecel',
    };
    const cardBgClasses = {
        'MTN': 'bg-mtn',
        'AT': 'bg-airteltigo',
        'AirtelTigo': 'bg-airteltigo',
        'Telecel': 'bg-telecel',
    };
    return {
        packages: [],
        networks: [],
        activeTab: 'all',
        search: '',
        loading: true,
        syncing: false,
        networkLogos,

        networkKey(network) {
            if (!network) return null;
            const n = String(network).toLowerCase();
            if (n.includes('mtn')) return 'MTN';
            if (n.includes('at') || n.includes('airtel') || n.includes('tigo')) return 'AT';
            if (n.includes('telecel') || n.includes('vodafone')) return 'Telecel';
            return network;
        },
        getTabClass(net) {
            return tabClasses[net] || tabClasses[this.networkKey(net)] || null;
        },
        getCardGradient(network) {
            const key = this.networkKey(network);
            return cardGradients[key] || cardGradients[network] || 'bg-surface-muted';
        },
        getCardBgClass(network) {
            const key = this.networkKey(network);
            return cardBgClasses[key] || cardBgClasses[network] || 'bg-surface-muted';
        },
        isMtnCard(network) {
            return this.networkKey(network) === 'MTN';
        },
        getNetworkLogo(network) {
            const key = this.networkKey(network);
            return this.networkLogos[key] || this.networkLogos[network] || this.networkLogos[network?.toUpperCase()] || null;
        },

        get filteredPackages() {
            const term = (this.search || '').toLowerCase();
            return this.packages.filter(p => {
                const matchTab = this.activeTab === 'all' || p.network === this.activeTab;
                const matchSearch = !term || (p.plan_label && p.plan_label.toLowerCase().includes(term)) ||
                    (p.volume_gb && p.volume_gb.toLowerCase().includes(term)) ||
                    (p.network && p.network.toLowerCase().includes(term));
                return matchTab && matchSearch;
            });
        },

        async init() {
            if (!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            await this.fetchPackages();
        },

        async fetchPackages() {
            this.loading = true;
            try {
                const response = await fetch('/api/admin/packages', {
                    headers: {
                        'Authorization': `Bearer ${this.$store.auth.token}`,
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) throw new Error('Fetch failed');
                const data = await response.json();
                const raw = Array.isArray(data) ? data : (data.data || data.packages || []);
                this.packages = raw.map(p => ({ ...p, saving: false }));
                const nets = [...new Set(this.packages.map(p => p.network).filter(Boolean))];
                this.networks = nets.length ? nets : ['MTN', 'Telecel', 'AirtelTigo'];
                if (!this.activeTab || (this.activeTab !== 'all' && !this.networks.includes(this.activeTab))) this.activeTab = 'all';
            } catch (e) {
                console.error(e);
                this.packages = [];
                this.networks = ['MTN', 'Telecel', 'AirtelTigo'];
                this.activeTab = 'all';
            } finally {
                this.loading = false;
            }
        },

        async syncPackages() {
            this.syncing = true;
            try {
                const response = await fetch('/api/admin/packages/sync', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.$store.auth.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    alert(result.message || 'Sync failed. Check JAYBART_API_KEY in .env and run: php artisan config:clear');
                    return;
                }
                await this.fetchPackages();
                if (result.message) alert(result.message + (result.total != null ? ' Created: ' + (result.created || 0) + ', Updated: ' + (result.updated || 0) : ''));
            } catch (e) {
                console.error(e);
                alert('Sync failed. Check API key in .env and network.');
            } finally {
                this.syncing = false;
            }
        },

        async savePackage(pkg) {
            if (pkg.saving === true) return;
            const sellingPrice = Number(pkg.selling_price);
            if (isNaN(sellingPrice) || sellingPrice < 0) {
                alert('Please enter a valid selling price (0 or greater).');
                return;
            }
            pkg.saving = true;
            const pkgRef = pkg;
            try {
                const token = (this.$store && this.$store.auth && this.$store.auth.token) || '';
                const body = {
                    selling_price: sellingPrice,
                    walking_customer_price: pkg.walking_customer_price != null && pkg.walking_customer_price !== ''
                        ? Number(pkg.walking_customer_price)
                        : null
                };
                const response = await fetch('/api/admin/packages/' + pkg.id, {
                    method: 'PUT',
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(body)
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const msg = result.message || (result.errors && (result.errors.selling_price && result.errors.selling_price[0]) || Object.values(result.errors || {}).flat()[0]) || 'Update failed';
                    alert(msg);
                    return;
                }
                if (result.package) {
                    pkgRef.selling_price = result.package.selling_price;
                    pkgRef.display_price = result.package.display_price;
                    pkgRef.plan_label = result.package.plan_label;
                }
                alert('Price updated. Users will see the new selling price.');
            } catch (e) {
                console.error(e);
                alert('Failed to update price. Check the console or try again.');
            } finally {
                pkgRef.saving = false;
            }
        }
    };
}
</script>
</x-layouts.admin>
