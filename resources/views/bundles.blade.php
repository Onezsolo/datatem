<x-layouts.public title="Buy data" description="Choose an affordable MTN, AirtelTigo or Telecel data bundle and pay instantly.">
    @php
        $networkLogos = [
            'MTN' => asset('assets/mtn.jpg'),
            'AT' => asset('assets/airtelTigo.png'),
            'AirtelTigo' => asset('assets/airtelTigo.png'),
            'Telecel' => asset('assets/telecel.png'),
        ];
        $cardBgClasses = ['MTN' => 'bg-mtn', 'AT' => 'bg-airteltigo', 'AirtelTigo' => 'bg-airteltigo', 'Telecel' => 'bg-telecel'];
    @endphp

    <div
        class="container-page py-8 sm:py-10"
        x-data="bundlesPage()"
        x-init="init()"
        data-network-logos='@json($networkLogos)'
        data-card-bg='@json($cardBgClasses)'
    >
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-muted">Buy data</p>
                <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-brand-navy">Choose your bundle</h1>
                <p class="mt-1 text-sm text-muted">Affordable data for MTN, AirtelTigo &amp; Telecel. Delivered instantly.</p>
            </div>

            <div class="rounded-card border border-line bg-surface px-4 py-2.5 text-right" x-data x-cloak>
                <p class="text-[0.6875rem] font-bold uppercase tracking-wide text-muted">Wallet balance</p>
                <p class="text-lg font-extrabold text-brand-navy" x-text="'GHS ' + Number($store.auth.user.balance || 0).toFixed(2)"></p>
            </div>
        </header>

        <template x-if="!$store.auth.token && packages.length === 0">
            <p class="mt-4 rounded-card border border-line bg-surface p-4 text-sm font-semibold text-brand-navy">
                <a href="{{ route('login') }}" class="underline underline-offset-4">Log in</a> to see prices and buy data.
            </p>
        </template>

        <div class="mt-6 flex flex-wrap gap-2" role="tablist" aria-label="Filter by network">
            <button @click="activeNet = 'all'" :class="activeNet === 'all' ? 'bg-brand-navy text-white border-brand-navy' : 'border-line bg-surface text-muted hover:text-brand-navy'" class="min-h-11 rounded-control border px-4 text-sm font-bold" :aria-selected="activeNet === 'all'">All</button>
            <button @click="activeNet = 'mtn'" :class="activeNet === 'mtn' ? 'bg-mtn text-brand-navy border-mtn' : 'border-line bg-surface text-muted hover:text-brand-navy'" class="min-h-11 rounded-control border px-4 text-sm font-bold" :aria-selected="activeNet === 'mtn'">MTN</button>
            <button @click="activeNet = 'at'" :class="activeNet === 'at' ? 'bg-airteltigo text-white border-airteltigo' : 'border-line bg-surface text-muted hover:text-brand-navy'" class="min-h-11 rounded-control border px-4 text-sm font-bold" :aria-selected="activeNet === 'at'">AirtelTigo</button>
            <button @click="activeNet = 'telecel'" :class="activeNet === 'telecel' ? 'bg-telecel text-white border-telecel' : 'border-line bg-surface text-muted hover:text-brand-navy'" class="min-h-11 rounded-control border px-4 text-sm font-bold" :aria-selected="activeNet === 'telecel'">Telecel</button>
        </div>

        <template x-if="loading">
            <div class="py-16 text-center">
                <p class="text-sm font-semibold text-muted">Loading packages...</p>
            </div>
        </template>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" x-show="!loading">
            <template x-for="pkg in filteredPackages" :key="pkg.id">
                <div class="flex flex-col rounded-card border border-line border-l-4 bg-surface p-5" :class="{ 'border-l-mtn': networkSlug(pkg.network) === 'mtn', 'border-l-airteltigo': networkSlug(pkg.network) === 'at', 'border-l-telecel': networkSlug(pkg.network) === 'telecel' }">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-control bg-surface-muted">
                            <img x-show="getNetworkLogo(pkg.network)" :src="getNetworkLogo(pkg.network)" :alt="pkg.network" class="h-9 w-9 object-contain">
                            <span x-show="!getNetworkLogo(pkg.network)" class="text-sm font-bold text-muted" x-text="(pkg.network || '').charAt(0)"></span>
                        </div>
                        <span class="inline-flex min-h-6 items-center rounded-control bg-surface-muted px-2 text-xs font-bold text-brand-navy" x-text="pkg.volume_gb || pkg.network"></span>
                    </div>

                    <h2 class="mt-3 text-base font-bold text-brand-navy" x-text="pkg.plan_name || (pkg.volume_gb + ' - GHS ' + pkg.price)"></h2>
                    <p class="mt-0.5 text-sm text-muted" x-text="pkg.network"></p>

                    <div class="mt-auto flex items-center justify-between gap-3 border-t border-line pt-4">
                        <span class="text-lg font-extrabold text-brand-navy">GHS <span x-text="parseFloat(pkg.price).toFixed(2)"></span></span>
                        <button @click="selectPackage(pkg)" class="min-h-11 rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Select</button>
                    </div>
                </div>
            </template>
        </div>

        <template x-if="!loading && filteredPackages.length === 0">
            <div class="mt-6 rounded-card border border-line bg-surface p-8 text-center">
                <p class="text-sm font-semibold text-brand-navy">No packages available right now.</p>
                <p x-show="!$store.auth.token" class="mt-1 text-sm text-muted">Log in to see packages and buy data.</p>
            </div>
        </template>

        <div
            x-show="showOrderModal"
            x-cloak
            class="fixed inset-0 z-[150] overflow-y-auto"
            role="dialog"
            aria-modal="true"
            aria-labelledby="order-modal-title"
        >
            <div x-show="showOrderModal" class="fixed inset-0 bg-brand-navy/60" @click="showOrderModal = false"></div>

            <div class="relative flex min-h-screen items-center justify-center p-4">
                <div x-show="showOrderModal" class="relative w-full max-w-md overflow-hidden rounded-card border border-line bg-surface shadow-lg">
                    <div class="flex items-center justify-between gap-3 border-b border-line p-5">
                        <div class="flex min-w-0 items-center gap-3">
                            <img :src="selectedPackage.logo" class="h-10 w-10 shrink-0 rounded-control bg-surface-muted object-contain p-1" x-show="selectedPackage.logo" alt="">
                            <div class="min-w-0">
                                <h2 id="order-modal-title" class="truncate text-base font-bold text-brand-navy" x-text="selectedPackage.plan_name || selectedPackage.name"></h2>
                                <p class="text-sm text-muted">GHS <span x-text="selectedPackage.price ? parseFloat(selectedPackage.price).toFixed(2) : ''"></span></p>
                            </div>
                        </div>
                        <button @click="showOrderModal = false" class="inline-flex h-10 w-10 items-center justify-center rounded-control text-muted hover:bg-surface-muted hover:text-brand-navy" aria-label="Close order dialog">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="p-5">
                        <template x-if="!$store.auth.token">
                            <div class="mb-5 rounded-card border border-brand-red-strong bg-surface p-4">
                                <p class="text-sm font-semibold text-brand-navy">Please log in to purchase.</p>
                                <a href="{{ route('login') }}" class="mt-2 inline-block text-sm font-bold text-brand-navy underline underline-offset-4">Log in</a>
                            </div>
                        </template>

                        <template x-if="$store.auth.token">
                            <div>
                                <div class="mb-5 flex rounded-control border border-line bg-surface-muted p-1">
                                    <button @click="orderType = 'single'" :class="orderType === 'single' ? 'bg-surface text-brand-navy' : 'text-muted'" class="min-h-10 flex-1 rounded-control text-sm font-bold">Single</button>
                                    <button @click="orderType = 'bulk'" :class="orderType === 'bulk' ? 'bg-surface text-brand-navy' : 'text-muted'" class="min-h-10 flex-1 rounded-control text-sm font-bold">Bulk</button>
                                </div>

                                <div x-show="orderType === 'single'" class="mb-5">
                                    <label for="beneficiary" class="block text-sm font-bold text-brand-navy">Mobile number</label>
                                    <input id="beneficiary" type="tel" name="beneficiary" x-model="beneficiary" placeholder="0551234567" class="mt-1 h-11 w-full rounded-control border border-line bg-surface px-3 text-sm text-ink">
                                </div>

                                <div x-show="orderType === 'bulk'" class="mb-5">
                                    <label for="bulk-beneficiaries" class="block text-sm font-bold text-brand-navy">Numbers (one per line or comma-separated)</label>
                                    <textarea id="bulk-beneficiaries" name="beneficiaries" x-model="bulkBeneficiaries" rows="4" placeholder="0551234567&#10;0249876543" class="mt-1 w-full rounded-control border border-line bg-surface px-3 py-2 text-sm text-ink"></textarea>
                                </div>

                                <button @click="submitOrder()" :disabled="submitting" class="min-h-11 w-full rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong disabled:opacity-60">
                                    <span x-text="submitting ? 'Processing...' : 'Place order'"></span>
                                </button>
                            </div>
                        </template>

                        <button @click="showOrderModal = false" class="mt-3 min-h-10 w-full text-sm font-semibold text-muted hover:text-brand-navy">Cancel</button>
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
                    return cardBg[key] || cardBg[network] || 'bg-brand-navy';
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
                        window.showAlert('Please select a package.');
                        return;
                    }
                    if (this.orderType === 'single') {
                        const num = (this.beneficiary || '').replace(/\s/g, '');
                        if (!num || num.length < 10) {
                            window.showAlert('Enter a valid Ghana mobile number (e.g. 0551234567).');
                            return;
                        }
                    } else {
                        const lines = (this.bulkBeneficiaries || '').split(/[\n,]+/).map(s => s.replace(/\s/g, '')).filter(s => s.length >= 10);
                        if (lines.length === 0) {
                            window.showAlert('Enter at least one valid number per line or comma-separated.');
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
                        window.showAlert(err);
                    } catch (e) {
                        console.error(e);
                        window.showAlert('Request failed. Try again.');
                    } finally {
                        this.submitting = false;
                    }
                }
            };
        }
    </script>
</x-layouts.public>
