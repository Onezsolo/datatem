<x-layouts.member title="Orders">
<style>
.orders-page { font-family: inherit; }
.orders-page .filter-tab { background: #f1f5f9; border: 1px solid #e2e8f0; color: #64748b; font-weight: 600; transition: all 0.2s; }
.orders-page .filter-tab:hover { background: #e2e8f0; color: #0f172a; }
.orders-page .filter-tab.active { background: var(--color-brand-navy); border-color: var(--color-brand-navy); color: white; }
</style>
<div class="orders-page min-h-screen bg-surface-muted py-8 md:py-12" x-data="transactionHistory()" x-init="init()">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <p class="text-muted text-sm font-semibold uppercase tracking-wider mb-0.5">Orders</p>
                <h1 class="text-2xl md:text-3xl font-bold text-brand-navy">Transaction history</h1>
            </div>
            <button @click="exportTransactions()" 
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-surface border border-line rounded-card text-sm font-semibold text-ink hover:bg-surface-muted hover:border-cyan-200 hover:text-brand-navy-soft transition-colors shadow-none w-full sm:w-auto">
                <svg class="w-5 h-5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export
            </button>
        </header>

        <!-- Filter tabs -->
        <div class="flex flex-wrap gap-2 mb-6">
            <button @click="setTab('all')" 
                    :class="activeTab === 'all' ? 'filter-tab active' : 'filter-tab'" 
                    class="px-5 py-2.5 rounded-card text-sm">All</button>
            <button @click="setTab('data')" 
                    :class="activeTab === 'data' ? 'filter-tab active' : 'filter-tab'" 
                    class="px-5 py-2.5 rounded-card text-sm">Data</button>
            <button @click="setTab('topup')" 
                    :class="activeTab === 'topup' ? 'filter-tab active' : 'filter-tab'" 
                    class="px-5 py-2.5 rounded-card text-sm">Topups</button>
        </div>

        <!-- Table card -->
        <div class="bg-surface rounded-card border border-line shadow-none overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left min-w-[640px]">
                    <thead>
                        <tr class="bg-surface-muted border-b border-line">
                            <th class="px-5 py-4 text-xs font-semibold text-muted uppercase tracking-wider">ID</th>
                            <th class="px-5 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Details</th>
                            <th class="px-5 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Date</th>
                            <th class="px-5 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Amount</th>
                            <th class="px-5 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Status</th>
                            <th class="px-5 py-4 text-xs font-semibold text-muted uppercase tracking-wider w-14"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <template x-for="transaction in filteredTransactions" :key="transaction.id">
                            <tr class="hover:bg-surface-muted/80 transition-colors group">
                                <td class="px-5 py-4">
                                    <span class="font-semibold text-brand-navy text-sm" x-text="transaction.id"></span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <template x-if="transaction.type === 'data'">
                                            <div class="w-10 h-10 rounded-lg bg-surface-muted flex items-center justify-center p-1 shrink-0">
                                                <img :src="transaction.logo === 'MTN' ? '{{ asset('assets/mtn.jpg') }}' : (transaction.logo === 'AT' ? '{{ asset('assets/airtelTigo.png') }}' : '{{ asset('assets/telecel.png') }}')" class="w-full h-full object-contain" :alt="transaction.logo">
                                            </div>
                                        </template>
                                        <template x-if="transaction.type === 'topup'">
                                            <div class="w-10 h-10 bg-line text-brand-navy rounded-lg flex items-center justify-center shrink-0">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </div>
                                        </template>
                                        <div class="min-w-0">
                                            <span class="font-semibold text-brand-navy block truncate" x-text="transaction.description"></span>
                                            <span class="text-xs text-muted" x-text="transaction.type === 'data' ? 'Bundle' : 'Topup'"></span>
                                            <template x-if="transaction.type === 'data' && transaction.number">
                                                <span class="text-xs text-muted block mt-0.5" x-text="transaction.number"></span>
                                            </template>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm text-muted font-medium" x-text="transaction.date"></td>
                                <td class="px-5 py-4">
                                    <span class="font-semibold text-brand-navy" x-text="transaction.amount"></span>
                                </td>
                                <td class="px-5 py-4">
                                    <span :class="{
                                        'bg-emerald-100 text-emerald-700': transaction.status === 'Completed',
                                        'bg-amber-100 text-amber-700': transaction.status === 'Pending',
                                        'bg-red-100 text-red-700': transaction.status === 'Failed'
                                    }" class="inline-block px-3 py-1.5 rounded-lg text-xs font-semibold uppercase" x-text="transaction.status"></span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <button @click="openReceipt(transaction)" class="p-2 rounded-lg text-muted hover:text-brand-navy hover:bg-surface-muted transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        
                        <template x-if="loading">
                            <tr>
                                <td colspan="6" class="px-5 py-16 text-center">
                                    <div class="animate-spin w-8 h-8 border-2 border-brand-navy border-t-transparent rounded-full mx-auto mb-3"></div>
                                    <p class="text-muted font-medium text-sm">Loading...</p>
                                </td>
                            </tr>
                        </template>

                        <template x-if="!loading && filteredTransactions.length === 0">
                            <tr>
                                <td colspan="6" class="px-5 py-16 text-center">
                                    <div class="w-14 h-14 bg-surface-muted rounded-card flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-7 h-7 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    </div>
                                    <h3 class="text-lg font-bold text-brand-navy mb-1">No transactions yet</h3>
                                    <p class="text-muted text-sm max-w-xs mx-auto">Buy data or top up to see your history here.</p>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-4 bg-surface-muted border-t border-line flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs font-medium text-muted">
                    <span x-text="pagination.total ? ((pagination.current_page - 1) * pagination.per_page + 1) : 0"></span>–<span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span> of <span x-text="pagination.total"></span>
                </p>
                <div class="flex items-center gap-2">
                    <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="px-3 py-2 rounded-lg text-sm font-semibold border border-line text-ink disabled:opacity-50 disabled:cursor-not-allowed hover:bg-surface hover:border-cyan-200 hover:text-brand-navy-soft transition-colors">Previous</button>
                    <span class="text-sm font-medium text-muted px-2" x-text="pagination.current_page + ' / ' + (pagination.last_page || 1)"></span>
                    <button @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="px-3 py-2 rounded-lg text-sm font-semibold border border-line text-ink disabled:opacity-50 disabled:cursor-not-allowed hover:bg-surface hover:border-cyan-200 hover:text-brand-navy-soft transition-colors">Next</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div x-show="receiptOpen" 
         x-cloak
         class="fixed inset-0 z-[100] overflow-y-auto"
         role="dialog"
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="receiptOpen" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 @click="receiptOpen = false"
                 class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>

            <div x-show="receiptOpen" 
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 class="relative bg-surface w-full max-w-md rounded-card shadow-none overflow-hidden border border-line">
                
                <div class="p-6 border-b border-line flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-surface-muted rounded-card flex items-center justify-center p-2">
                            <img src="{{ \App\Services\SettingsService::getLogoUrl() }}" alt="{{ \App\Services\SettingsService::get('site_name', 'Datatem') }}" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-brand-navy">Receipt</h3>
                            <p class="text-xs font-medium text-muted" x-text="'ID ' + selectedReceipt?.id"></p>
                        </div>
                    </div>
                    <button @click="receiptOpen = false" class="p-2 rounded-lg text-muted hover:text-brand-navy hover:bg-surface-muted">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-5">
                    <div class="flex justify-between items-center py-3 border-b border-line">
                        <span class="text-xs font-semibold text-muted uppercase tracking-wider">Type</span>
                        <span class="font-semibold text-brand-navy" x-text="selectedReceipt?.type === 'data' ? 'Bundle' : 'Topup'"></span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-line">
                        <span class="text-xs font-semibold text-muted uppercase tracking-wider">Status</span>
                        <span :class="{
                            'text-emerald-600': selectedReceipt?.status === 'Completed',
                            'text-amber-600': selectedReceipt?.status === 'Pending',
                            'text-red-600': selectedReceipt?.status === 'Failed'
                        }" class="font-semibold" x-text="selectedReceipt?.status"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted">Description</span>
                        <span class="font-medium text-brand-navy text-right max-w-[180px]" x-text="selectedReceipt?.description"></span>
                    </div>
                    <template x-if="selectedReceipt?.type === 'data' && selectedReceipt?.number">
                        <div class="flex justify-between text-sm">
                            <span class="text-muted">Number</span>
                            <span class="font-medium text-brand-navy" x-text="selectedReceipt?.number"></span>
                        </div>
                    </template>
                    <div class="flex justify-between text-sm">
                        <span class="text-muted">Date</span>
                        <span class="font-medium text-brand-navy" x-text="selectedReceipt?.date"></span>
                    </div>
                    <div class="pt-4 flex justify-between items-end border-t-2 border-line">
                        <div>
                            <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-0.5">Amount</p>
                            <p class="text-2xl font-bold text-brand-navy" x-text="selectedReceipt?.amount"></p>
                        </div>
                        <template x-if="selectedReceipt?.type === 'data'">
                            <div class="flex items-center gap-2">
                                <img :src="(selectedReceipt?.logo === 'MTN') ? '{{ asset('assets/mtn.jpg') }}' : (selectedReceipt?.logo === 'AT' ? '{{ asset('assets/airtelTigo.png') }}' : '{{ asset('assets/telecel.png') }}')" :alt="selectedReceipt?.logo || ''" class="h-7 w-7 object-contain rounded">
                                <span class="font-semibold text-brand-navy text-sm" x-text="selectedReceipt?.logo === 'MTN' ? 'MTN' : (selectedReceipt?.logo === 'AT' ? 'AirtelTigo' : (selectedReceipt?.logo || ''))"></span>
                            </div>
                        </template>
                    </div>

                    <button @click="receiptOpen = false" class="w-full mt-4 py-3.5 rounded-card bg-brand-navy text-white font-semibold hover:bg-brand-navy transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Invite & Earn Popup (after purchase) -->
    <div x-show="inviteEarnPopupOpen"
         x-cloak
         class="fixed inset-0 z-[110] overflow-y-auto"
         role="dialog"
         aria-modal="true"
         aria-labelledby="invite-earn-title">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div x-show="inviteEarnPopupOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 @click="closeInviteEarn()"
                 class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div x-show="inviteEarnPopupOpen"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200 transform"
                 class="relative bg-surface w-full max-w-md rounded-card shadow-none border border-line overflow-hidden">
                <div class="p-6 sm:p-8 text-center">
                    <div class="flex items-center justify-center gap-4 mb-4">
                        <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-card flex items-center justify-center shrink-0">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div class="inline-flex items-baseline rounded-card bg-line px-4 py-2.5 border border-cyan-200">
                            <span class="text-2xl sm:text-3xl font-bold text-brand-navy-soft">GHS 1.00</span>
                        </div>
                    </div>
                    <h2 id="invite-earn-title" class="text-xl font-bold text-brand-navy mb-2">Invite & Earn</h2>
                    <p class="text-muted font-medium text-sm max-w-sm mx-auto mb-6">Earn when friends sign up. Share your link.</p>
                    <div class="flex flex-col gap-3">
                        <a :href="whatsAppShareUrl" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 w-full bg-[#25D366] text-white font-semibold py-3.5 px-5 rounded-card hover:bg-[#1fb355] transition-colors text-sm">
                            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.414 0 0 5.414 0 12.05c0 2.123.551 4.2 1.6 6.04L0 24l6.101-1.601A11.77 11.77 0 0012.05 24.1c6.636 0 12.05-5.414 12.05-12.05 0-3.212-1.25-6.231-3.513-8.5zm-.002 0z"/></svg>
                            Share on WhatsApp
                        </a>
                        <a href="/referral" class="inline-flex items-center justify-center w-full py-3 rounded-card font-semibold bg-surface-muted text-ink hover:bg-surface-muted hover:text-brand-navy-soft hover:border-cyan-200 border border-transparent transition-colors text-sm">My referral link</a>
                        <button @click="closeInviteEarn()" class="text-muted font-medium text-sm hover:text-muted py-2">Maybe later</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function transactionHistory() {
    return {
        activeTab: 'all',
        pagination: { current_page: 1, last_page: 1, total: 0, per_page: 20 },
        loading: true,
        receiptOpen: false,
        selectedReceipt: null,
        transactions: [],
        inviteEarnPopupOpen: false,

        get referralUrl() {
            const code = this.$store.auth.user && this.$store.auth.user.affiliate && this.$store.auth.user.affiliate.referral_code;
            if (!code) return window.location.origin + '/signup';
            return window.location.origin + '/signup?ref=' + encodeURIComponent(code);
        },

        get whatsAppShareUrl() {
            const text = 'Get cheap data on Datatem! Sign up with my link: ' + this.referralUrl;
            return 'https://wa.me/?text=' + encodeURIComponent(text);
        },

        closeInviteEarn() {
            this.inviteEarnPopupOpen = false;
            const url = new URL(window.location.href);
            url.searchParams.delete('success');
            window.history.replaceState({}, '', url.pathname + (url.search || ''));
        },

        async init() {
            if(!this.$store.auth.token) {
                window.location.href='/login';
                return;
            }
            await this.fetchTransactions();
            const params = new URLSearchParams(window.location.search);
            if (params.has('success') && this.$store.auth.user && this.$store.auth.user.affiliate_system_enabled) {
                this.inviteEarnPopupOpen = true;
            }
        },

        setTab(tab) {
            this.activeTab = tab;
            this.pagination.current_page = 1;
            this.fetchTransactions();
        },

        async fetchTransactions(page) {
            if (page != null) this.pagination.current_page = page;
            this.loading = true;
            try {
                const type = this.activeTab === 'all' ? 'all' : (this.activeTab === 'data' ? 'data' : 'topup');
                const response = await fetch('/api/auth/user/transactions?page=' + this.pagination.current_page + '&per_page=' + this.pagination.per_page + '&type=' + type, {
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' }
                });
                if(response.ok) {
                    const data = await response.json();
                    this.transactions = data.data !== undefined ? data.data : data;
                    if (data.current_page != null) {
                        this.pagination.current_page = data.current_page;
                        this.pagination.last_page = data.last_page ?? 1;
                        this.pagination.total = data.total ?? this.transactions.length;
                        this.pagination.per_page = data.per_page ?? 20;
                    }
                }
            } catch (e) {
                console.error('Failed to fetch transactions:', e);
            } finally {
                this.loading = false;
            }
        },
        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.fetchTransactions(page);
        },

        get filteredTransactions() {
            return this.transactions;
        },

        openReceipt(transaction) {
            this.selectedReceipt = transaction;
            this.receiptOpen = true;
        },

        exportTransactions() {
            if (this.filteredTransactions.length === 0) {
                alert('No transactions to export.');
                return;
            }

            const headers = ['Transaction ID', 'Type', 'Description', 'Number', 'Date', 'Amount', 'Status'];
            const rows = this.filteredTransactions.map(t => [
                t.id,
                t.type.toUpperCase(),
                t.description,
                t.type === 'data' ? (t.number || '') : '',
                t.date,
                t.amount.replace('GHS ', ''),
                t.status
            ]);

            let csvContent = "data:text/csv;charset=utf-8," 
                + headers.join(",") + "\n"
                + rows.map(e => e.join(",")).join("\n");

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `Datatem_Transactions_${this.activeTab}_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    }
}
</script>
</x-layouts.member>
