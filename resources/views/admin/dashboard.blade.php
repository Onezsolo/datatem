<x-layouts.admin title="Dashboard">
<div class="admin-page min-h-screen bg-surface-muted py-8" 
     x-data="adminDashboard()" 
     x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
            <div>
                <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-1">Admin Console</p>
                <h1 class="text-3xl font-bold text-brand-navy tracking-tight">Welcome, <span class="text-brand-navy" x-text="(($store.auth.user.full_name || 'Admin').trim().split(/\s+/).pop()) || 'Admin'"></span></h1>
                <p class="text-muted mt-1 text-sm">Overview of platform health and performance.</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="fetchStats()" class="btn-admin-secondary px-5 py-2.5 flex items-center gap-2 text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refresh
                </button>
            </div>
        </div>

        <p x-show="statsError" x-text="statsError" class="mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-card text-sm font-medium"></p>

        <!-- Primary Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-10">
            <div class="admin-card p-6">
                <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Amount Spent (Site)</p>
                <p class="text-2xl font-bold text-brand-navy"><span class="text-muted text-sm">GHS</span> <span x-text="stats.totalSpent != null ? Number(stats.totalSpent).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00'"></span></p>
            </div>
            <div class="admin-card p-6">
                <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Today's Amount Spent</p>
                <p class="text-2xl font-bold text-brand-navy"><span class="text-muted text-sm">GHS</span> <span x-text="stats.todaySpent != null ? Number(stats.todaySpent).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00'"></span></p>
            </div>
            <a href="/admin/users" class="admin-card p-6 hover:border-line transition-colors cursor-pointer block">
                <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Total Users Balance</p>
                <p class="text-2xl font-bold text-brand-navy"><span class="text-muted text-sm">GHS</span> <span x-text="stats.totalUsersBalance != null ? Number(stats.totalUsersBalance).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00'"></span></p>
                <p class="text-xs text-muted mt-1">Deducts when users purchase</p>
            </a>
            <div class="admin-card p-6 bg-emerald-50/50 border-emerald-100">
                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider mb-2">Profit (Sales)</p>
                <p class="text-2xl font-bold text-emerald-700"><span class="text-sm">GHS</span> <span x-text="stats.totalProfit != null ? Number(stats.totalProfit).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00'"></span></p>
                <p class="text-xs text-emerald-600/80 mt-1">Amount − package cost</p>
            </div>
            <div class="admin-card p-6 bg-emerald-50/50 border-emerald-100">
                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider mb-2">Today's Profit</p>
                <p class="text-2xl font-bold text-emerald-700"><span class="text-sm">GHS</span> <span x-text="stats.todayProfit != null ? Number(stats.todayProfit).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00'"></span></p>
            </div>
            <a href="/admin/users" class="admin-card p-6 hover:border-line transition-colors cursor-pointer block">
                <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-2">Total Users</p>
                <p class="text-2xl font-bold text-brand-navy" x-text="stats.usersCount || 0"></p>
            </a>
        </div>

        <!-- Management Overview -->
        <h2 class="text-lg font-bold text-brand-navy mb-4">Management Overview</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-10">
            <a href="/admin/orders" class="admin-card p-6 hover:border-line transition-all group block">
                <div class="w-12 h-12 bg-cyan-100 rounded-card flex items-center justify-center text-brand-navy mb-4 group-hover:scale-105 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
                <p class="text-2xl font-bold text-brand-navy" x-text="stats.ordersCount || 0"></p>
                <p class="text-xs font-semibold text-muted uppercase tracking-wider">Total Orders</p>
            </a>
            <a href="/admin/topups" class="admin-card p-6 hover:border-line transition-all group block">
                <div class="w-12 h-12 bg-emerald-100 rounded-card flex items-center justify-center text-emerald-600 mb-4 group-hover:scale-105 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-2xl font-bold text-brand-navy" x-text="stats.topupsCount || 0"></p>
                <p class="text-xs font-semibold text-muted uppercase tracking-wider">Wallet Topups</p>
            </a>
            <a href="/admin/payments" class="admin-card p-6 hover:border-line transition-all group block">
                <div class="w-12 h-12 bg-amber-100 rounded-card flex items-center justify-center text-amber-600 mb-4 group-hover:scale-105 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <p class="text-2xl font-bold text-brand-navy" x-text="stats.paymentsCount || 0"></p>
                <p class="text-xs font-semibold text-muted uppercase tracking-wider">Manual Payments</p>
            </a>
            <a href="/admin/support" class="admin-card p-6 hover:border-line transition-all group block">
                <div class="w-12 h-12 bg-rose-100 rounded-card flex items-center justify-center text-rose-600 mb-4 group-hover:scale-105 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <p class="text-2xl font-bold text-brand-navy" x-text="stats.ticketsCount || 0"></p>
                <p class="text-xs font-semibold text-muted uppercase tracking-wider">Support Tickets</p>
            </a>
            <a href="/admin/resellers" class="admin-card p-6 hover:border-line transition-all group block">
                <div class="w-12 h-12 bg-cyan-100 rounded-card flex items-center justify-center text-brand-navy mb-4 group-hover:scale-105 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
                <p class="text-2xl font-bold text-brand-navy" x-text="stats.resellersCount || 0"></p>
                <p class="text-xs font-semibold text-muted uppercase tracking-wider">Reseller Requests</p>
            </a>
            <a href="/admin/affiliates" class="admin-card p-6 hover:border-line transition-all group block">
                <div class="w-12 h-12 bg-emerald-100 rounded-card flex items-center justify-center text-emerald-600 mb-4 group-hover:scale-105 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8v8M3 21h18M3 10h18M3 7l9-4 9 4M3 10l9 4m0 0l9-4m-9 4v11"/></svg>
                </div>
                <p class="text-2xl font-bold text-brand-navy" x-text="stats.affiliatesCount || 0"></p>
                <p class="text-xs font-semibold text-muted uppercase tracking-wider">Affiliates</p>
            </a>
            <a href="/admin/spin-preview" target="_blank" rel="noopener noreferrer" class="admin-card p-6 border-2 border-dashed border-amber-200 hover:border-amber-300 hover:bg-amber-50/30 transition-all group block relative">
                <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[10px] font-semibold uppercase">Preview</span>
                <div class="w-12 h-12 bg-amber-100 rounded-card flex items-center justify-center text-amber-600 mb-4 group-hover:scale-105 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
                <p class="text-xl font-bold text-brand-navy">Lucky Spin</p>
                <p class="text-xs font-semibold text-muted uppercase tracking-wider">Coming Soon</p>
            </a>
        </div>

        <!-- Recent Activity -->
        <div class="admin-card overflow-hidden">
            <div class="p-6 border-b border-line flex flex-wrap items-center justify-between gap-4 bg-surface-muted/50">
                <h3 class="text-lg font-bold text-brand-navy">Recent Activity</h3>
                <div class="flex items-center gap-4">
                    <div class="flex rounded-card border border-line overflow-hidden bg-surface">
                        <button @click="activeTab = 'orders'" :class="activeTab === 'orders' ? 'bg-brand-navy text-white' : 'text-muted hover:bg-surface-muted'" class="px-4 py-2 text-sm font-semibold transition-colors">Orders</button>
                        <button @click="activeTab = 'topups'" :class="activeTab === 'topups' ? 'bg-brand-navy text-white' : 'text-muted hover:bg-surface-muted'" class="px-4 py-2 text-sm font-semibold transition-colors">Topups</button>
                        <button @click="activeTab = 'users'" :class="activeTab === 'users' ? 'bg-brand-navy text-white' : 'text-muted hover:bg-surface-muted'" class="px-4 py-2 text-sm font-semibold transition-colors">Signups</button>
                    </div>
                    <a :href="activeTab === 'orders' ? '/admin/orders' : activeTab === 'topups' ? '/admin/topups' : '/admin/users'" class="text-sm font-semibold text-brand-navy hover:underline">View all</a>
                </div>
            </div>
            <div class="p-6 min-h-[260px]">
                <template x-if="activityError">
                    <p class="text-amber-600 font-bold text-sm" x-text="activityError"></p>
                </template>
                <template x-if="!activityError && activeTab === 'orders'">
                    <ul class="space-y-3">
                        <template x-for="item in (recentActivity.recent_orders || [])" :key="item.id + '-order'">
                            <li class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                                <div>
                                    <p class="font-bold text-brand-navy text-sm" x-text="item.description"></p>
                                    <p class="text-xs text-muted" x-text="item.user ? 'By ' + item.user : ''"></p>
                                </div>
                                <div class="text-right">
                                    <span class="font-black text-brand-navy" x-text="'GHS ' + (item.amount != null ? Number(item.amount).toFixed(2) : '0.00')"></span>
                                    <span class="ml-2 px-2 py-0.5 rounded text-[10px] font-bold uppercase" :class="item.status === 'delivered' ? 'bg-green-100 text-green-700' : item.status === 'processing' ? 'bg-purple-100 text-purple-700' : item.status === 'pending' ? 'bg-orange-100 text-orange-700' : 'bg-surface-muted text-muted'" x-text="item.status"></span>
                                    <p class="text-xs text-muted mt-0.5" x-text="item.created_at ? new Date(item.created_at).toLocaleString() : ''"></p>
                                </div>
                            </li>
                        </template>
                        <li x-show="!recentActivity.recent_orders?.length && !activityLoading" class="text-muted font-bold text-sm py-8 text-center">No recent orders</li>
                    </ul>
                </template>
                <template x-if="!activityError && activeTab === 'topups'">
                    <ul class="space-y-3">
                        <template x-for="item in (recentActivity.recent_topups || [])" :key="item.id + '-topup'">
                            <li class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                                <div>
                                    <p class="font-bold text-brand-navy text-sm" x-text="item.description"></p>
                                    <p class="text-xs text-muted" x-text="item.user ? 'By ' + item.user : ''"></p>
                                </div>
                                <div class="text-right">
                                    <span class="font-black text-brand-navy" x-text="'GHS ' + (item.amount != null ? Number(item.amount).toFixed(2) : '0.00')"></span>
                                    <p class="text-xs text-muted mt-0.5" x-text="item.created_at ? new Date(item.created_at).toLocaleString() : ''"></p>
                                </div>
                            </li>
                        </template>
                        <li x-show="!recentActivity.recent_topups?.length && !activityLoading" class="text-muted font-bold text-sm py-8 text-center">No recent topups</li>
                    </ul>
                </template>
                <template x-if="!activityError && activeTab === 'users'">
                    <ul class="space-y-3">
                        <template x-for="item in (recentActivity.recent_users || [])" :key="item.id + '-user'">
                            <li class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                                <p class="font-bold text-brand-navy text-sm" x-text="item.description"></p>
                                <p class="text-xs text-muted" x-text="item.created_at ? new Date(item.created_at).toLocaleString() : ''"></p>
                            </li>
                        </template>
                        <li x-show="!recentActivity.recent_users?.length && !activityLoading" class="text-muted font-bold text-sm py-8 text-center">No recent signups</li>
                    </ul>
                </template>
                <p x-show="activityLoading" class="text-muted font-bold text-sm py-8 text-center">Loading…</p>
            </div>
        </div>
    </div>
</div>

<script>
function adminDashboard() {
    return {
        stats: {},
        statsError: '',
        recentActivity: { recent_orders: [], recent_topups: [], recent_users: [] },
        activityError: '',
        activityLoading: false,
        activeTab: 'orders',
        async init() {
            if(!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            await this.fetchStats();
            await this.fetchRecentActivity();
        },
        async fetchStats() {
            this.statsError = '';
            try {
                const url = '{{ url("/api/admin/stats") }}';
                const response = await fetch(url, {
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json'
                    }
                });
                if (!response.ok) {
                    const err = await response.json().catch(() => ({}));
                    this.statsError = err.error || err.message || 'Could not load stats (' + response.status + ')';
                    if (response.status === 401 || response.status === 403) {
                        setTimeout(() => { window.location.href = '/login'; }, 1500);
                    }
                    return;
                }
                this.stats = await response.json();
            } catch (e) {
                console.error(e);
                this.statsError = 'Network error. Check console.';
            }
        },
        async fetchRecentActivity() {
            this.activityLoading = true;
            this.activityError = '';
            try {
                const response = await fetch('{{ url("/api/admin/recent-activity") }}', {
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Failed to load activity');
                this.recentActivity = await response.json();
            } catch (e) {
                console.error(e);
                this.activityError = 'Could not load recent activity.';
            } finally {
                this.activityLoading = false;
            }
        }
    }
}
</script>
</x-layouts.admin>
