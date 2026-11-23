@extends('layouts.app')

@section('content')
<div class="admin-page min-h-screen bg-gray-50 py-8" 
     x-data="orderManagement()" 
     x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <a href="/admin/dashboard" class="admin-back mb-2 inline-flex items-center text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to Dashboard
                </a>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Inventory</p>
                <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Orders</h1>
                <p class="text-slate-500 mt-1 text-sm">Monitor and manage data transaction orders.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="button" @click="checkAllOrdersStatus()" :disabled="checkingAllStatus"
                        class="btn-admin-primary px-5 py-2.5 flex items-center gap-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg x-show="!checkingAllStatus" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <span x-show="checkingAllStatus" class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full"></span>
                    <span x-text="checkingAllStatus ? 'Checking…' : 'Check processing status'"></span>
                </button>
                <button @click="exportCSV()" class="btn-admin-secondary px-5 py-2.5 flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export CSV
                </button>
            </div>
        </div>

        <div class="admin-card overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="relative flex-1 max-w-md flex gap-2">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" x-model="search" @input="onSearchInput()" @keydown.enter="clearSearchDebounce(); fetchOrders(1)"
                           placeholder="Search orders (ID, number, customer)..."
                           class="w-full pl-12 pr-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 outline-none text-slate-900">
                    <button type="button" @click="clearSearchDebounce(); fetchOrders(1)" class="btn-admin-primary px-5 py-3 shrink-0 text-sm">Search</button>
                </div>
                <select x-model="statusFilter" @change="fetchOrders(1)" class="px-4 py-3 rounded-xl bg-white border border-slate-200 outline-none focus:border-cyan-500 font-medium text-slate-600 text-sm">
                    <option value="all">All Status</option>
                    <option value="delivered">Delivered</option>
                    <option value="processing">Processing</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
            <div class="overflow-x-auto min-h-[400px]">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th @click="sortBy('id')" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-cyan-600">Order <span x-show="sortField === 'id'" x-text="sortOrder === 'asc' ? '↑' : '↓'"></span></th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Number</th>
                            <th @click="sortBy('amount')" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-cyan-600">Amount <span x-show="sortField === 'amount'" x-text="sortOrder === 'asc' ? '↑' : '↓'"></span></th>
                            <th @click="sortBy('profit')" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-cyan-600">Profit <span x-show="sortField === 'profit'" x-text="sortOrder === 'asc' ? '↑' : '↓'"></span></th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Status</th>
                            <th @click="sortBy('created_at')" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-cyan-600 text-right">Date <span x-show="sortField === 'created_at'" x-text="sortOrder === 'asc' ? '↑' : '↓'"></span></th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="order in orders" :key="order.id">
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-slate-900" x-text="order.id"></p>
                                    <p class="text-xs text-cyan-600" x-text="order.package"></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-slate-700" x-text="order.customer || order.user?.full_name || '—'"></p>
                                    <p x-show="order.source === 'storefront' && order.source_label" class="text-xs text-cyan-600 mt-0.5">Storefront: <span x-text="order.source_label"></span></p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-mono text-sm text-slate-600" x-text="order.number"></p>
                                    <p x-show="order.source === 'storefront'" class="text-xs text-cyan-600 mt-0.5">Storefront<span x-show="order.source_label">: <span x-text="order.source_label"></span></span></p>
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-900">GHS <span x-text="parseFloat(order.amount).toFixed(2)"></span></td>
                                <td class="px-6 py-4 font-medium" :class="order.profit != null && order.profit >= 0 ? 'text-emerald-600' : 'text-slate-500'">GHS <span x-text="order.profit != null ? Number(order.profit).toFixed(2) : '—'"></span></td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold border"
                                          :class="{
                                              'bg-emerald-50 text-emerald-700 border-emerald-100': order.status === 'delivered',
                                              'bg-purple-50 text-purple-700 border-purple-100': order.status === 'processing',
                                              'bg-amber-50 text-amber-700 border-amber-100': order.status === 'pending',
                                              'bg-red-50 text-red-700 border-red-100': order.status === 'failed'
                                          }" x-text="order.status"></span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-slate-500" x-text="order.created_at ? new Date(order.created_at).toLocaleString(undefined, { dateStyle: 'short', timeStyle: 'short', hour12: true }) : ''"></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <template x-if="order.status === 'pending'">
                                            <button type="button" @click="resendOrder(order)" :disabled="resendingId === order.id"
                                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-100 text-amber-700 border border-amber-200 hover:bg-amber-200 disabled:opacity-50">
                                                <span x-show="resendingId !== order.id">Resend</span>
                                                <span x-show="resendingId === order.id">Sending…</span>
                                            </button>
                                        </template>
                                        <template x-if="order.transaction_id">
                                            <button type="button" @click="checkOrderStatus(order)" :disabled="checkingStatusId === order.id"
                                                    class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-cyan-100 text-cyan-700 border border-cyan-200 hover:bg-cyan-200 disabled:opacity-50">
                                                <span x-show="checkingStatusId !== order.id">Check status</span>
                                                <span x-show="checkingStatusId === order.id">Checking…</span>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="orders.length === 0">
                            <td colspan="8" class="px-6 py-16 text-center text-slate-500">No orders found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="p-6 border-t border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm text-slate-500">Showing <span x-text="pagination.total ? ((pagination.current_page - 1) * pagination.per_page + 1) : 0"></span>–<span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span> of <span x-text="pagination.total"></span></p>
                <div class="flex items-center gap-2">
                    <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="btn-admin-secondary px-4 py-2 text-sm disabled:opacity-50">Previous</button>
                    <span class="text-sm text-slate-600" x-text="'Page ' + pagination.current_page + ' of ' + (pagination.last_page || 1)"></span>
                    <button @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="btn-admin-secondary px-4 py-2 text-sm disabled:opacity-50">Next</button>
                    <button @click="fetchOrders()" class="p-2 text-slate-400 hover:text-cyan-600 rounded-xl" title="Refresh"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function orderManagement() {
    return {
        orders: [],
        pagination: { current_page: 1, last_page: 1, total: 0, per_page: 20 },
        search: '',
        statusFilter: 'all',
        sortField: 'created_at',
        sortOrder: 'desc',
        resendingId: null,
        checkingStatusId: null,
        checkingAllStatus: false,
        searchDebounceTimer: null,

        async init() {
            if(!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            await this.fetchOrders();
        },

        buildOrdersParams(page) {
            const p = new URLSearchParams();
            if (page != null) p.set('page', page);
            p.set('per_page', this.pagination.per_page);
            if (this.search && this.search.trim()) p.set('search', this.search.trim());
            if (this.statusFilter && this.statusFilter !== 'all') p.set('status', this.statusFilter);
            p.set('sort_by', this.sortField);
            p.set('sort_dir', this.sortOrder);
            return p.toString();
        },
        clearSearchDebounce() {
            if (this.searchDebounceTimer) {
                clearTimeout(this.searchDebounceTimer);
                this.searchDebounceTimer = null;
            }
        },
        onSearchInput() {
            this.clearSearchDebounce();
            this.searchDebounceTimer = setTimeout(() => {
                this.fetchOrders(1);
                this.searchDebounceTimer = null;
            }, 450);
        },
        async fetchOrders(page) {
            if (page != null) this.pagination.current_page = page;
            try {
                const response = await fetch('/api/admin/orders?' + this.buildOrdersParams(this.pagination.current_page), {
                    headers: { 'Authorization': `Bearer ${this.$store.auth.token}`, 'Accept': 'application/json' }
                });
                if(!response.ok) throw new Error('Fetch failed');
                const data = await response.json();
                this.orders = data.data !== undefined ? data.data : data;
                if (data.current_page != null) {
                    this.pagination.current_page = data.current_page;
                    this.pagination.last_page = data.last_page ?? 1;
                    this.pagination.total = data.total ?? this.orders.length;
                    this.pagination.per_page = data.per_page ?? 20;
                }
            } catch (e) { console.error(e); }
        },
        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.fetchOrders(page);
        },
        sortBy(field) {
            if (this.sortField === field) {
                this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortField = field;
                this.sortOrder = 'desc';
            }
            this.fetchOrders(1);
        },

        async resendOrder(order) {
            if (order.status !== 'pending' || this.resendingId) return;
            this.resendingId = order.id;
            try {
                const response = await fetch('/api/admin/orders/' + order.id + '/resend', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    showAlert(data.error || 'Failed to resend order.', 'Error', 'error');
                    return;
                }
                await this.fetchOrders();
            } catch (e) {
                console.error(e);
                showAlert('Failed to resend order.', 'Error', 'error');
            } finally {
                this.resendingId = null;
            }
        },

        async checkAllOrdersStatus() {
            if (this.checkingAllStatus) return;
            this.checkingAllStatus = true;
            try {
                const response = await fetch('/api/admin/orders/check-all-status', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    showAlert(data.error || 'Failed to check all orders.', 'Error', 'error');
                    return;
                }
                await this.fetchOrders();
                const msg = data.message || ('Checked ' + (data.checked || 0) + ', updated ' + (data.updated || 0) + '.');
                if (data.errors && data.errors.length) {
                    showAlert(msg + '\n\nSome errors:\n' + data.errors.slice(0, 3).join('\n'), 'Check complete', 'info');
                } else {
                    showAlert(msg, 'Check complete', 'success');
                }
            } catch (e) {
                console.error(e);
                showAlert('Failed to check all orders.', 'Error', 'error');
            } finally {
                this.checkingAllStatus = false;
            }
        },

        async checkOrderStatus(order) {
            if (!order.transaction_id || this.checkingStatusId) return;
            this.checkingStatusId = order.id;
            try {
                const response = await fetch('/api/admin/orders/' + order.id + '/check-status', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    showAlert(data.error || 'Failed to check status.', 'Error', 'error');
                    return;
                }
                await this.fetchOrders();
            } catch (e) {
                console.error(e);
                showAlert('Failed to check status.', 'Error', 'error');
            } finally {
                this.checkingStatusId = null;
            }
        },

        async exportCSV() {
            try {
                const params = new URLSearchParams({ export: '1' });
                if (this.search && this.search.trim()) params.set('search', this.search.trim());
                if (this.statusFilter && this.statusFilter !== 'all') params.set('status', this.statusFilter);
                params.set('sort_by', this.sortField);
                params.set('sort_dir', this.sortOrder);
                const response = await fetch('/api/admin/orders?' + params.toString(), {
                    headers: { 'Authorization': `Bearer ${this.$store.auth.token}`, 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Export failed');
                const data = await response.json();
                const orders = data.data || data;
                if (!orders.length) {
                    if (typeof showAlert === 'function') showAlert('No orders to export.', 'Info', 'info');
                    else alert('No orders to export.');
                    return;
                }
                const headers = ['Order ID', 'Package', 'Customer', 'Number', 'Amount', 'Profit', 'Status', 'Date'];
                const rows = orders.map(o => [
                    o.id,
                    (o.package || '').replace(/,/g, ' '),
                    (o.customer || o.user?.full_name || 'System').replace(/,/g, ' '),
                    o.number || o.beneficiary || '',
                    o.amount,
                    o.profit != null ? o.profit : '',
                    o.status,
                    new Date(o.created_at).toLocaleString()
                ]);
                let csvContent = "data:text/csv;charset=utf-8,\uFEFF" + headers.join(",") + "\n" + rows.map(e => e.map(c => '"' + String(c).replace(/"/g, '""') + '"').join(",")).join("\n");
                const link = document.createElement("a");
                link.setAttribute("href", encodeURI(csvContent));
                link.setAttribute("download", `platform_orders_${new Date().toISOString().split('T')[0]}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (e) {
                console.error(e);
                if (typeof showAlert === 'function') showAlert('Export failed.', 'Error', 'error');
                else alert('Export failed.');
            }
        }
    }
}
</script>
@endsection
