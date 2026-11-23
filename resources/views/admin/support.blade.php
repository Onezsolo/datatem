@extends('layouts.app')

@section('content')
<div class="admin-page min-h-screen bg-gray-50 py-8" 
     x-data="supportManagement()" 
     x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <a href="/admin/dashboard" class="admin-back mb-2 inline-flex items-center text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to Dashboard
                </a>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Support</p>
                <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Tickets</h1>
                <p class="text-slate-500 mt-1 text-sm">Manage and respond to user inquiries.</p>
            </div>
            <button @click="exportCSV()" class="btn-admin-secondary px-5 py-2.5 flex items-center gap-2 text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </button>
        </div>

        <div class="mb-6 flex gap-2 max-w-md">
            <div class="relative flex-1">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" x-model="search" @keydown.enter="fetchTickets(1)" placeholder="Search (ID, subject, customer)..." class="w-full pl-12 pr-4 py-3 rounded-xl bg-white border border-slate-200 focus:border-cyan-500 outline-none text-slate-900">
            </div>
            <button type="button" @click="fetchTickets(1)" class="btn-admin-primary px-5 py-3 shrink-0 text-sm">Search</button>
        </div>

        <div class="admin-card overflow-hidden">
            <div class="overflow-x-auto min-h-[400px]">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th @click="sortBy('id')" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-cyan-600">ID</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Subject</th>
                            <th @click="sortBy('status')" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-cyan-600">Status</th>
                            <th @click="sortBy('created_at')" class="px-6 py-4 text-xs font-semibold text-slate-500 uppercase tracking-wider cursor-pointer hover:text-cyan-600 text-right">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="ticket in tickets" :key="ticket.id">
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-6 py-4 font-semibold text-slate-900" x-text="ticket.id"></td>
                                <td class="px-6 py-4 text-slate-700" x-text="ticket.user?.full_name || 'System'"></td>
                                <td class="px-6 py-4 text-slate-600" x-text="ticket.subject"></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold border"
                                          :class="ticket.status === 'open' ? 'bg-cyan-50 text-cyan-700 border-cyan-100' : 'bg-slate-100 text-slate-500 border-slate-200'"
                                          x-text="ticket.status"></span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-slate-500" x-text="new Date(ticket.created_at).toLocaleString()"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="p-6 border-t border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm text-slate-500">Showing <span x-text="pagination.total ? ((pagination.current_page - 1) * pagination.per_page + 1) : 0"></span>–<span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span> of <span x-text="pagination.total"></span></p>
                <div class="flex items-center gap-2">
                    <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="btn-admin-secondary px-4 py-2 text-sm disabled:opacity-50">Previous</button>
                    <span class="text-sm text-slate-600" x-text="'Page ' + pagination.current_page + ' of ' + (pagination.last_page || 1)"></span>
                    <button @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="btn-admin-secondary px-4 py-2 text-sm disabled:opacity-50">Next</button>
                    <button @click="fetchTickets()" class="p-2 text-slate-400 hover:text-cyan-600 rounded-xl" title="Refresh"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function supportManagement() {
    return {
        tickets: [],
        pagination: { current_page: 1, last_page: 1, total: 0, per_page: 20 },
        search: '',
        sortField: 'created_at',
        sortOrder: 'desc',

        async init() {
            if(!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            await this.fetchTickets();
        },

        buildTicketsParams(page) {
            const p = new URLSearchParams();
            if (page != null) p.set('page', page);
            p.set('per_page', this.pagination.per_page);
            if (this.search && this.search.trim()) p.set('search', this.search.trim());
            p.set('sort_by', this.sortField);
            p.set('sort_dir', this.sortOrder);
            return p.toString();
        },
        async fetchTickets(page) {
            if (page != null) this.pagination.current_page = page;
            try {
                const response = await fetch('/api/admin/tickets?' + this.buildTicketsParams(this.pagination.current_page), {
                    headers: { 'Authorization': `Bearer ${this.$store.auth.token}`, 'Accept': 'application/json' }
                });
                if(!response.ok) throw new Error('Fetch failed');
                const data = await response.json();
                this.tickets = data.data !== undefined ? data.data : data;
                if (data.current_page != null) {
                    this.pagination.current_page = data.current_page;
                    this.pagination.last_page = data.last_page ?? 1;
                    this.pagination.total = data.total ?? this.tickets.length;
                    this.pagination.per_page = data.per_page ?? 20;
                }
            } catch (e) { console.error(e); }
        },
        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.fetchTickets(page);
        },
        sortBy(field) {
            if (this.sortField === field) this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            else { this.sortField = field; this.sortOrder = 'desc'; }
            this.fetchTickets(1);
        },
        async exportCSV() {
            try {
                const params = new URLSearchParams({ export: '1' });
                if (this.search && this.search.trim()) params.set('search', this.search.trim());
                params.set('sort_by', this.sortField);
                params.set('sort_dir', this.sortOrder);
                const response = await fetch('/api/admin/tickets?' + params.toString(), {
                    headers: { 'Authorization': `Bearer ${this.$store.auth.token}`, 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Export failed');
                const data = await response.json();
                const list = data.data || data;
                if (!list.length) {
                    showAlert('No tickets to export.', 'Info', 'info');
                    return;
                }
                const headers = ['ID', 'Customer', 'Subject', 'Status', 'Date'];
                const escape = (v) => '"' + String(v).replace(/"/g, '""') + '"';
                const rows = list.map(t => [t.id, (t.user?.full_name || 'System').replace(/,/g, ' '), (t.subject || '').replace(/,/g, ' '), t.status, new Date(t.created_at).toLocaleString()]);
                let csvContent = "data:text/csv;charset=utf-8,\uFEFF" + headers.join(",") + "\n" + rows.map(e => e.map(escape).join(",")).join("\n");
                const link = document.createElement("a");
                link.setAttribute("href", encodeURI(csvContent));
                link.setAttribute("download", "support_export_" + new Date().toISOString().split('T')[0] + ".csv");
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
@endsection
