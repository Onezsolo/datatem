<x-layouts.admin title="Top-ups">
<div class="admin-page min-h-screen bg-surface-muted py-8" 
     x-data="topupManagement()" 
     x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <a href="/admin/dashboard" class="admin-back mb-2 inline-flex items-center text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to Dashboard
                </a>
                <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-1">Finance</p>
                <h1 class="text-3xl font-bold text-brand-navy tracking-tight">Topups</h1>
                <p class="text-muted mt-1 text-sm">Completed topups and pending Paystack. Use &quot;Check status&quot; to verify and credit.</p>
            </div>
            <button @click="exportCSV()" class="btn-admin-secondary px-5 py-2.5 flex items-center gap-2 text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </button>
        </div>

        <div class="mb-6 flex gap-2 max-w-md">
            <div class="relative flex-1">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" @keydown.enter="fetchTopups(1)" placeholder="Search (ID, reference, customer)..." class="w-full pl-12 pr-4 py-3 rounded-card bg-surface border border-line focus:border-brand-navy outline-none text-brand-navy">
            </div>
            <button type="button" @click="fetchTopups(1)" class="btn-admin-primary px-5 py-3 shrink-0 text-sm">Search</button>
        </div>

        <div class="admin-card overflow-hidden">
            <div class="overflow-x-auto min-h-[400px]">
                <table class="w-full text-left">
                    <thead class="bg-surface-muted border-b border-line">
                        <tr>
                            <th @click="sortBy('id')" class="px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider cursor-pointer hover:text-brand-navy">Reference</th>
                            <th class="px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">User</th>
                            <th @click="sortBy('amount')" class="px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider cursor-pointer hover:text-brand-navy">Amount</th>
                            <th class="px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Method</th>
                            <th class="px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider text-center">Status</th>
                            <th class="px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <template x-for="row in topups" :key="row.record_type + '-' + row.id">
                            <tr class="hover:bg-surface-muted/80 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-brand-navy font-mono text-sm" x-text="row.reference || row.id"></p>
                                    <p class="text-xs text-muted mt-0.5" x-text="row.created_at ? new Date(row.created_at).toLocaleString(undefined, { dateStyle: 'short', timeStyle: 'short', hour12: true }) : ''"></p>
                                </td>
                                <td class="px-6 py-4">
                                    <template x-if="row.record_type === 'storefront_pending' || row.record_type === 'storefront_completed'">
                                        <div>
                                            <p class="font-semibold text-brand-navy">Storefront</p>
                                            <p class="text-xs text-muted mt-0.5" x-text="(row.reseller?.store_name || row.reseller?.full_name) || 'Reseller'"></p>
                                        </div>
                                    </template>
                                    <template x-if="row.record_type !== 'storefront_pending' && row.record_type !== 'storefront_completed'">
                                        <div>
                                            <p class="font-medium text-ink" x-text="row.user?.full_name || 'System'"></p>
                                            <p class="text-xs text-muted mt-0.5" x-text="row.user?.email || ''" x-show="row.user?.email"></p>
                                        </div>
                                    </template>
                                </td>
                                <td class="px-6 py-4 font-semibold text-emerald-600">GHS <span x-text="parseFloat(row.amount).toFixed(2)"></span></td>
                                <td class="px-6 py-4 text-muted text-sm" x-text="row.method"></td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold border"
                                          :class="row.status === 'approved' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : (row.record_type === 'storefront_completed' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : (row.record_type === 'storefront_pending' ? 'bg-surface-muted text-brand-navy-soft border-cyan-100' : (row.record_type === 'paystack_pending' ? 'bg-amber-50 text-amber-700 border-amber-100' : 'bg-surface-muted text-muted border-line')))"
                                          x-text="row.record_type === 'storefront_completed' ? 'Completed (Storefront)' : (row.record_type === 'storefront_pending' ? 'Pending (Storefront)' : (row.record_type === 'paystack_pending' ? 'Pending (Paystack)' : row.status))"></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <template x-if="row.record_type === 'storefront_pending'"><span class="text-muted text-xs">Completed on callback</span></template>
                                    <template x-if="row.record_type === 'storefront_completed'"><span class="text-muted text-xs">—</span></template>
                                    <template x-if="row.record_type === 'paystack_pending'">
                                        <button @click="checkPaystackStatus(row.id)" :disabled="verifyingId === row.id" class="btn-admin-primary px-3 py-1.5 text-xs disabled:opacity-50">
                                            <span x-show="verifyingId !== row.id">Check status</span>
                                            <span x-show="verifyingId === row.id">Checking…</span>
                                        </button>
                                    </template>
                                    <template x-if="row.record_type === 'topup' && row.status === 'pending'">
                                        <div class="flex justify-end gap-2">
                                            <button @click="approve(row.id)" class="bg-brand-navy text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-cyan-700">Approve</button>
                                            <button class="bg-red-50 text-red-600 px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-red-100">Reject</button>
                                        </div>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            
            <div class="p-8 border-t border-line bg-surface-muted/50 flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm font-bold text-muted">
                    Showing <span x-text="pagination.total ? ((pagination.current_page - 1) * pagination.per_page + 1) : 0"></span>–<span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span> of <span x-text="pagination.total"></span> items
                </p>
                <div class="flex items-center gap-2">
                    <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="px-4 py-2 rounded-card font-bold border border-line disabled:opacity-50 disabled:cursor-not-allowed hover:bg-surface-muted">Previous</button>
                    <span class="px-3 py-1 text-sm font-bold text-muted" x-text="'Page ' + pagination.current_page + ' of ' + (pagination.last_page || 1)"></span>
                    <button @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="px-4 py-2 rounded-card font-bold border border-line disabled:opacity-50 disabled:cursor-not-allowed hover:bg-surface-muted">Next</button>
                    <button @click="fetchTopups()" class="p-2 text-muted hover:text-brand-navy rounded-card" title="Refresh"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function topupManagement() {
    return {
        topups: [],
        pagination: { current_page: 1, last_page: 1, total: 0, per_page: 20 },
        search: '',
        sortField: 'created_at',
        sortOrder: 'desc',
        verifyingId: null,

        async init() {
            if(!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            await this.fetchTopups();
        },

        buildTopupsParams(page) {
            const p = new URLSearchParams();
            if (page != null) p.set('page', page);
            p.set('per_page', this.pagination.per_page);
            if (this.search && this.search.trim()) p.set('search', this.search.trim());
            p.set('sort_by', this.sortField);
            p.set('sort_dir', this.sortOrder);
            return p.toString();
        },
        async fetchTopups(page) {
            if (page != null) this.pagination.current_page = page;
            try {
                const response = await fetch('/api/admin/topups?' + this.buildTopupsParams(this.pagination.current_page), {
                    headers: { 'Authorization': `Bearer ${this.$store.auth.token}`, 'Accept': 'application/json' }
                });
                if(!response.ok) throw new Error('Fetch failed');
                const data = await response.json();
                this.topups = data.data !== undefined ? data.data : data;
                if (data.current_page != null) {
                    this.pagination.current_page = data.current_page;
                    this.pagination.last_page = data.last_page ?? 1;
                    this.pagination.total = data.total ?? this.topups.length;
                    this.pagination.per_page = data.per_page ?? 20;
                }
            } catch (e) { console.error(e); }
        },
        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.fetchTopups(page);
        },
        sortBy(field) {
            if (this.sortField === field) this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            else { this.sortField = field; this.sortOrder = 'desc'; }
            this.fetchTopups(1);
        },

        async checkPaystackStatus(reference) {
            this.verifyingId = reference;
            try {
                const response = await fetch(`/api/admin/payments/${encodeURIComponent(reference)}/verify-paystack`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.$store.auth.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json().catch(() => ({}));
                if (response.ok && data.verified) {
                    await this.fetchTopups();
                    showAlert('Payment verified with Paystack. User has been credited and notified.', 'Success', 'success');
                } else {
                    showAlert(data.message || data.error || 'Verification failed. Payment may not be successful on Paystack.', 'Verification failed', 'error');
                }
            } catch (e) {
                console.error(e);
                showAlert('Failed to verify with Paystack.', 'Error', 'error');
            } finally {
                this.verifyingId = null;
            }
        },

        async approve(id) {
            if (!confirm('Credit this user\'s wallet and notify them?')) return;
            try {
                const response = await fetch(`/api/admin/topups/${id}/approve`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.$store.auth.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json().catch(() => ({}));
                if (response.ok) {
                    await this.fetchTopups();
                    showAlert('Topup approved. User has been notified (in-app + SMS).', 'Success', 'success');
                } else {
                    showAlert(data.error || data.message || 'Failed to approve.', 'Error', 'error');
                }
            } catch (e) {
                console.error(e);
                showAlert('Failed to approve topup.', 'Error', 'error');
            }
        },

        async exportCSV() {
            try {
                const params = new URLSearchParams({ export: '1' });
                if (this.search && this.search.trim()) params.set('search', this.search.trim());
                params.set('sort_by', this.sortField);
                params.set('sort_dir', this.sortOrder);
                const response = await fetch('/api/admin/topups?' + params.toString(), {
                    headers: { 'Authorization': `Bearer ${this.$store.auth.token}`, 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Export failed');
                const data = await response.json();
                const list = data.data || data;
                if (!list.length) {
                    showAlert('No topups to export.', 'Info', 'info');
                    return;
                }
                const headers = ['ID', 'User', 'Amount', 'Method', 'Status', 'Type', 'Date'];
                const rows = list.map(t => [t.id, (t.user?.full_name || 'System').replace(/,/g, ' '), t.amount, t.method || '', t.record_type === 'paystack_pending' ? 'Pending (Paystack)' : (t.status || ''), t.record_type || 'topup', new Date(t.created_at).toLocaleString()]);
                const escape = (v) => '"' + String(v).replace(/"/g, '""') + '"';
                let csvContent = "data:text/csv;charset=utf-8,\uFEFF" + headers.join(",") + "\n" + rows.map(e => e.map(escape).join(",")).join("\n");
                const link = document.createElement("a");
                link.setAttribute("href", encodeURI(csvContent));
                link.setAttribute("download", "topups_export_" + new Date().toISOString().split('T')[0] + ".csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (e) {
                console.error(e);
                showAlert('Export failed.', 'Error', 'error');
            }
        }
    }
}
</script>
</x-layouts.admin>
