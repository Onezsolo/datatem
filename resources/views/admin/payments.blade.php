<x-layouts.admin title="Payments">
<div class="admin-page min-h-screen bg-surface-muted py-8" 
     x-data="paymentManagement()" 
     x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <a href="/admin/dashboard" class="admin-back mb-2 inline-flex items-center text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to Dashboard
                </a>
                <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-1">Finance</p>
                <h1 class="text-3xl font-bold text-brand-navy tracking-tight">Manual Payments</h1>
                <p class="text-muted mt-1 text-sm">Verify bank/MoMo references. Paystack is under <a href="/admin/topups" class="admin-back">Topups</a>.</p>
            </div>
            <button @click="exportCSV()" class="btn-admin-secondary px-5 py-2.5 flex items-center gap-2 text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </button>
        </div>

        <div class="mb-6 flex gap-2 max-w-md">
            <div class="relative flex-1">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" @keydown.enter="fetchPayments(1)" placeholder="Search (ID, reference, user)..." class="w-full pl-12 pr-4 py-3 rounded-card bg-surface border border-line focus:border-brand-navy outline-none text-brand-navy">
            </div>
            <button type="button" @click="fetchPayments(1)" class="btn-admin-primary px-5 py-3 shrink-0 text-sm">Search</button>
        </div>

        <div class="admin-card overflow-hidden">
            <div class="overflow-x-auto min-h-[400px]">
                <table class="w-full text-left">
                    <thead class="bg-surface-muted border-b border-line">
                        <tr>
                            <th @click="sortBy('id')" class="px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider cursor-pointer hover:text-brand-navy">ID</th>
                            <th class="px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">User</th>
                            <th @click="sortBy('amount')" class="px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider cursor-pointer hover:text-brand-navy">Amount</th>
                            <th class="px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider">Reference</th>
                            <th class="px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider text-center">Status</th>
                            <th class="px-6 py-4 text-xs font-semibold text-muted uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        <template x-for="payment in payments" :key="payment.id">
                            <tr class="hover:bg-surface-muted/80 transition-colors">
                                <td class="px-6 py-4 font-semibold text-brand-navy" x-text="payment.id"></td>
                                <td class="px-6 py-4 text-ink" x-text="payment.user?.full_name || 'System'"></td>
                                <td class="px-6 py-4 font-semibold text-brand-navy">GHS <span x-text="parseFloat(payment.amount).toFixed(2)"></span></td>
                                <td class="px-6 py-4 font-mono text-xs text-brand-navy" x-text="payment.reference"></td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold border border-amber-200 bg-amber-50 text-amber-700" x-text="payment.status"></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <template x-if="payment.status === 'pending'">
                                        <button class="btn-admin-primary px-3 py-1.5 text-xs">Verify</button>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="p-6 border-t border-line bg-surface-muted/50 flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm text-muted">Showing <span x-text="pagination.total ? ((pagination.current_page - 1) * pagination.per_page + 1) : 0"></span>–<span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span> of <span x-text="pagination.total"></span></p>
                <div class="flex items-center gap-2">
                    <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="btn-admin-secondary px-4 py-2 text-sm disabled:opacity-50">Previous</button>
                    <span class="text-sm text-muted" x-text="'Page ' + pagination.current_page + ' of ' + (pagination.last_page || 1)"></span>
                    <button @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="btn-admin-secondary px-4 py-2 text-sm disabled:opacity-50">Next</button>
                    <button @click="fetchPayments()" class="p-2 text-muted hover:text-brand-navy rounded-card" title="Refresh"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function paymentManagement() {
    return {
        payments: [],
        pagination: { current_page: 1, last_page: 1, total: 0, per_page: 20 },
        search: '',
        sortField: 'created_at',
        sortOrder: 'desc',

        async init() {
            if(!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            await this.fetchPayments();
        },

        buildPaymentsParams(page) {
            const p = new URLSearchParams();
            if (page != null) p.set('page', page);
            p.set('per_page', this.pagination.per_page);
            if (this.search && this.search.trim()) p.set('search', this.search.trim());
            p.set('sort_by', this.sortField);
            p.set('sort_dir', this.sortOrder);
            return p.toString();
        },
        async fetchPayments(page) {
            if (page != null) this.pagination.current_page = page;
            try {
                const response = await fetch('/api/admin/payments?' + this.buildPaymentsParams(this.pagination.current_page), {
                    headers: { 'Authorization': `Bearer ${this.$store.auth.token}`, 'Accept': 'application/json' }
                });
                if(!response.ok) throw new Error('Fetch failed');
                const data = await response.json();
                this.payments = data.data !== undefined ? data.data : (Array.isArray(data) ? data : []);
                if (data.current_page != null) {
                    this.pagination.current_page = data.current_page;
                    this.pagination.last_page = data.last_page ?? 1;
                    this.pagination.total = data.total ?? this.payments.length;
                    this.pagination.per_page = data.per_page ?? 20;
                }
            } catch (e) {
                console.error(e);
            }
        },
        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.fetchPayments(page);
        },
        sortBy(field) {
            if (this.sortField === field) this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            else { this.sortField = field; this.sortOrder = 'desc'; }
            this.fetchPayments(1);
        },
        async exportCSV() {
            try {
                const params = new URLSearchParams({ export: '1' });
                if (this.search && this.search.trim()) params.set('search', this.search.trim());
                params.set('sort_by', this.sortField);
                params.set('sort_dir', this.sortOrder);
                const response = await fetch('/api/admin/payments?' + params.toString(), {
                    headers: { 'Authorization': `Bearer ${this.$store.auth.token}`, 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Export failed');
                const data = await response.json();
                const list = data.data || (Array.isArray(data) ? data : []);
                if (!list.length) {
                    alert('No payments to export.');
                    return;
                }
                const headers = ['ID', 'User', 'Amount', 'Reference', 'Status', 'Date'];
                const escape = (v) => '"' + String(v).replace(/"/g, '""') + '"';
                const rows = list.map(p => [p.id, (p.user?.full_name || 'System').replace(/,/g, ' '), p.amount, p.reference || '', p.status, new Date(p.created_at).toLocaleString()]);
                let csvContent = "data:text/csv;charset=utf-8,\uFEFF" + headers.join(",") + "\n" + rows.map(e => e.map(escape).join(",")).join("\n");
                const link = document.createElement("a");
                link.setAttribute("href", encodeURI(csvContent));
                link.setAttribute("download", "payments_export_" + new Date().toISOString().split('T')[0] + ".csv");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (e) {
                console.error(e);
                alert('Export failed.');
            }
        }
    }
}
</script>
</x-layouts.admin>
