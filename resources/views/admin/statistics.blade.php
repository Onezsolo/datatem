<x-layouts.admin title="Finance overview">
<div class="admin-page min-h-screen bg-surface-muted py-8" x-data="financeStatistics()" x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <a href="/admin/dashboard" class="admin-back mb-4 inline-flex items-center text-sm">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Dashboard
            </a>
            <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-1">Finance</p>
            <h1 class="text-3xl font-bold text-brand-navy tracking-tight">Statistics</h1>
            <p class="text-muted mt-1 text-sm">Amount spent, balance, profit, payments, topups, orders with date filters.</p>
        </div>

        <div class="admin-card p-6 mb-6">
            <h3 class="text-lg font-bold text-brand-navy mb-4">Filter by date</h3>
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-1">From</label>
                    <input type="date" x-model="dateFrom" @change="applyFilter()" class="rounded-card border border-line px-4 py-3 font-medium text-brand-navy focus:border-brand-navy outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-1">To</label>
                    <input type="date" x-model="dateTo" @change="applyFilter()" class="rounded-card border border-line px-4 py-3 font-medium text-brand-navy focus:border-brand-navy outline-none">
                </div>
                <button @click="applyFilter()" class="btn-admin-primary px-5 py-3 text-sm">Apply</button>
                <button @click="setLast30()" class="btn-admin-secondary px-5 py-3 text-sm">Last 30 days</button>
            </div>
        </div>

        <p x-show="error" x-text="error" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-card text-sm font-bold"></p>

        <!-- Summary cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="admin-card p-6 bg-brand-navy text-white shadow-none">
                <h4 class="text-muted font-black uppercase tracking-widest text-[10px] mb-2">Amount Spent</h4>
                <p class="text-2xl font-black" x-text="'GHS ' + (data.amount_spent != null ? Number(data.amount_spent).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00')"></p>
                <p class="text-xs text-muted mt-1" x-text="'Period: ' + (data.date_from || '') + ' to ' + (data.date_to || '')"></p>
            </div>
            <div class="admin-card p-6 bg-surface shadow-none border border-line/50">
                <h4 class="text-muted font-black uppercase tracking-widest text-[10px] mb-2">Total Users Balance</h4>
                <p class="text-2xl font-black text-brand-navy" x-text="'GHS ' + (data.total_users_balance != null ? Number(data.total_users_balance).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00')"></p>
                <p class="text-xs text-muted mt-1">Current snapshot</p>
            </div>
            <div class="admin-card p-6 bg-green-50 shadow-none border border-green-100">
                <h4 class="text-green-600/60 font-black uppercase tracking-widest text-[10px] mb-2">Profit Earned</h4>
                <p class="text-2xl font-black text-green-700" x-text="'GHS ' + (data.profit_earned != null ? Number(data.profit_earned).toLocaleString(undefined, {minimumFractionDigits: 2}) : '0.00')"></p>
            </div>
            <div class="admin-card p-6 bg-surface shadow-none border border-line/50">
                <h4 class="text-muted font-black uppercase tracking-widest text-[10px] mb-2">Total Orders</h4>
                <p class="text-2xl font-black text-brand-navy" x-text="data.total_orders != null ? data.total_orders : 0"></p>
            </div>
        </div>

        <!-- Shop finance (same dashboard as data) -->
        <div class="admin-card p-6 border border-line/50 bg-surface mb-10">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h3 class="text-lg font-black text-brand-navy">Shop &amp; service finance</h3>
                    <p class="text-xs text-muted mt-1">Shop orders and provider-backed services share the same finance dashboard as data.</p>
                </div>
                <a href="{{ route('admin.shop') }}" class="text-sm font-bold text-brand-navy underline underline-offset-4">Manage catalog</a>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div class="rounded-control border border-line bg-surface-muted p-4">
                    <p class="text-[10px] font-black uppercase tracking-wider text-muted">Shop revenue</p>
                    <p class="mt-1 text-xl font-black text-brand-navy" x-text="'GHS ' + Number(data.shop_revenue || 0).toFixed(2)"></p>
                </div>
                <div class="rounded-control border border-line bg-surface-muted p-4">
                    <p class="text-[10px] font-black uppercase tracking-wider text-muted">Paid shop orders</p>
                    <p class="mt-1 text-xl font-black text-brand-navy" x-text="data.shop_paid_orders || 0"></p>
                </div>
                <div class="rounded-control border border-line bg-surface-muted p-4">
                    <p class="text-[10px] font-black uppercase tracking-wider text-muted">Pending shop orders</p>
                    <p class="mt-1 text-xl font-black text-brand-navy" x-text="data.shop_pending_orders || 0"></p>
                </div>
                <div class="rounded-control border border-line bg-surface-muted p-4">
                    <p class="text-[10px] font-black uppercase tracking-wider text-muted">Delivery fees</p>
                    <p class="mt-1 text-xl font-black text-brand-navy" x-text="'GHS ' + Number(data.shop_delivery_fees || 0).toFixed(2)"></p>
                </div>
                <div class="rounded-control border border-line bg-surface-muted p-4">
                    <p class="text-[10px] font-black uppercase tracking-wider text-muted">Service revenue</p>
                    <p class="mt-1 text-xl font-black text-brand-navy" x-text="'GHS ' + Number(data.service_revenue || 0).toFixed(2)"></p>
                </div>
                <div class="rounded-control border border-line bg-surface-muted p-4">
                    <p class="text-[10px] font-black uppercase tracking-wider text-muted">Paid service orders</p>
                    <p class="mt-1 text-xl font-black text-brand-navy" x-text="data.service_paid_orders || 0"></p>
                </div>
                <div class="rounded-control border border-line bg-surface-muted p-4">
                    <p class="text-[10px] font-black uppercase tracking-wider text-muted">Refunded service orders</p>
                    <p class="mt-1 text-xl font-black text-brand-navy" x-text="data.service_refunded_orders || 0"></p>
                </div>            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-control border border-line bg-surface px-4 py-3">
                <span class="text-sm font-bold text-brand-navy">Combined revenue (data + shop + services)</span>
                <span class="text-lg font-extrabold text-brand-navy" x-text="'GHS ' + Number(data.combined_revenue || 0).toFixed(2)"></span>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-line bg-surface-muted">
                            <th class="px-4 py-3 text-xs font-black uppercase tracking-wider text-muted">Date</th>
                            <th class="px-4 py-3 text-xs font-black uppercase tracking-wider text-muted text-right">Paid shop orders</th>
                            <th class="px-4 py-3 text-xs font-black uppercase tracking-wider text-muted text-right">Shop sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in (data.shop_daily_breakdown || [])" :key="row.date">
                            <tr class="border-b border-line">
                                <td class="px-4 py-3 font-bold text-brand-navy" x-text="row.date"></td>
                                <td class="px-4 py-3 text-right font-black text-brand-navy" x-text="row.orders_count"></td>
                                <td class="px-4 py-3 text-right font-bold text-brand-navy" x-text="'GHS ' + Number(row.amount || 0).toFixed(2)"></td>
                            </tr>
                        </template>
                        <tr x-show="!(data.shop_daily_breakdown && data.shop_daily_breakdown.length)">
                            <td colspan="3" class="px-4 py-8 text-center text-sm font-bold text-muted">No shop sales in this period</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-line bg-surface-muted">
                            <th class="px-4 py-3 text-xs font-black uppercase tracking-wider text-muted">Date</th>
                            <th class="px-4 py-3 text-xs font-black uppercase tracking-wider text-muted text-right">Paid service orders</th>
                            <th class="px-4 py-3 text-xs font-black uppercase tracking-wider text-muted text-right">Service revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in (data.service_daily_breakdown || [])" :key="row.date">
                            <tr class="border-b border-line">
                                <td class="px-4 py-3 font-bold text-brand-navy" x-text="row.date"></td>
                                <td class="px-4 py-3 text-right font-black text-brand-navy" x-text="row.orders_count"></td>
                                <td class="px-4 py-3 text-right font-bold text-brand-navy" x-text="'GHS ' + Number(row.amount || 0).toFixed(2)"></td>
                            </tr>
                        </template>
                        <tr x-show="!(data.service_daily_breakdown && data.service_daily_breakdown.length)">
                            <td colspan="3" class="px-4 py-8 text-center text-sm font-bold text-muted">No service sales in this period</td>
                        </tr>
                    </tbody>
                </table>
            </div>        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
            <!-- Manual payments -->
            <div class="admin-card p-6 border border-line/50 bg-surface">
                <h3 class="text-lg font-black text-brand-navy mb-4">Manual Payments</h3>
                <ul class="space-y-3">
                    <li class="flex justify-between items-center py-2 border-b border-slate-50">
                        <span class="font-bold text-ink">Success (count)</span>
                        <span class="font-black text-brand-navy" x-text="data.manual_payments_success_count != null ? data.manual_payments_success_count : 0"></span>
                    </li>
                    <li class="flex justify-between items-center py-2 border-b border-slate-50">
                        <span class="font-bold text-ink">Success (amount)</span>
                        <span class="font-black text-green-600" x-text="'GHS ' + (data.manual_payments_success_amount != null ? Number(data.manual_payments_success_amount).toFixed(2) : '0.00')"></span>
                    </li>
                    <li class="flex justify-between items-center py-2 border-b border-slate-50">
                        <span class="font-bold text-ink">Failed</span>
                        <span class="font-black text-red-600" x-text="data.manual_payments_failed_count != null ? data.manual_payments_failed_count : 0"></span>
                    </li>
                    <li class="flex justify-between items-center py-2">
                        <span class="font-bold text-ink">Pending</span>
                        <span class="font-black text-amber-600" x-text="data.manual_payments_pending_count != null ? data.manual_payments_pending_count : 0"></span>
                    </li>
                </ul>
            </div>
            <!-- Wallet topups & affiliate -->
            <div class="admin-card p-6 border border-line/50 bg-surface">
                <h3 class="text-lg font-black text-brand-navy mb-4">Wallet Topups &amp; Affiliate</h3>
                <ul class="space-y-3">
                    <li class="flex justify-between items-center py-2 border-b border-slate-50">
                        <span class="font-bold text-ink">Wallet topups (count)</span>
                        <span class="font-black text-brand-navy" x-text="data.wallet_topups_count != null ? data.wallet_topups_count : 0"></span>
                    </li>
                    <li class="flex justify-between items-center py-2 border-b border-slate-50">
                        <span class="font-bold text-ink">Wallet topups (sum)</span>
                        <span class="font-black text-brand-navy" x-text="'GHS ' + (data.wallet_topups_sum != null ? Number(data.wallet_topups_sum).toFixed(2) : '0.00')"></span>
                    </li>
                    <li class="flex justify-between items-center py-2">
                        <span class="font-bold text-ink">Affiliate expenses (withdrawals)</span>
                        <span class="font-black text-brand-navy" x-text="'GHS ' + (data.affiliate_expenses != null ? Number(data.affiliate_expenses).toFixed(2) : '0.00')"></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Profit calculator -->
        <div class="admin-card p-6 border border-line/50 bg-surface mb-10">
            <h3 class="text-lg font-black text-brand-navy mb-2">Profit calculator</h3>
            <p class="text-sm text-muted mb-4">Enter amounts to compute profit. Use &quot;User balance at start&quot; with a date so the same balance is not counted again in the next period. After calculating, save the current balance as the start for next time.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-bold text-muted uppercase tracking-wider mb-1">Invested amount (GHS)</label>
                    <input type="number" step="0.01" min="0" x-model="calc.investedAmount" placeholder="e.g. 500" class="rounded-card border border-line px-4 py-3 w-full font-bold text-brand-navy" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-muted uppercase tracking-wider mb-1">Total from Paystack (GHS)</label>
                    <input type="number" step="0.01" min="0" x-model="calc.paystackTotal" placeholder="e.g. 699.38" class="rounded-card border border-line px-4 py-3 w-full font-bold text-brand-navy" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-muted uppercase tracking-wider mb-1">User balance at start (GHS)</label>
                    <input type="number" step="0.01" min="0" x-model="calc.userBalanceAtStart" placeholder="e.g. 0 or last snapshot" class="rounded-card border border-line px-4 py-3 w-full font-bold text-brand-navy" />
                    <p class="text-xs text-muted mt-1" x-show="calc.startBalanceDate">As at <span x-text="calc.startBalanceDate"></span> (last saved snapshot)</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-muted uppercase tracking-wider mb-1">Date of start balance</label>
                    <input type="date" x-model="calc.startBalanceDate" class="rounded-card border border-line px-4 py-3 w-full font-bold text-brand-navy" />
                </div>
                <div>
                    <label class="block text-xs font-bold text-muted uppercase tracking-wider mb-1">Current user balance (GHS)</label>
                    <input type="text" :value="data.total_users_balance != null ? Number(data.total_users_balance).toFixed(2) : '0.00'" readonly class="rounded-card border border-line px-4 py-3 w-full font-bold text-brand-navy bg-surface-muted" />
                    <p class="text-xs text-muted mt-1">From system (refresh stats to update)</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-muted uppercase tracking-wider mb-1">Current provider balance (GHS)</label>
                    <input type="number" step="0.01" min="0" x-model="calc.providerBalanceNow" placeholder="e.g. 8.75" class="rounded-card border border-line px-4 py-3 w-full font-bold text-brand-navy" />
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <button @click="doCalc()" class="px-6 py-3 rounded-card font-black bg-brand-navy text-white hover:bg-brand-navy-soft transition-colors">Calculate</button>
                <button @click="saveSnapshot()" :disabled="snapshotSaving || data.total_users_balance == null" class="px-6 py-3 rounded-card font-bold border border-line text-muted hover:bg-surface-muted transition-colors disabled:opacity-50">Save current balance as start for next time</button>
            </div>
            <div x-show="calc.result !== null" class="p-4 rounded-card bg-surface-muted border border-line">
                <p class="font-black text-brand-navy mb-2">Result</p>
                <ul class="space-y-1 text-sm">
                    <li><span class="font-bold text-muted">Revenue (spent by users):</span> <span class="font-black" x-text="'GHS ' + (calc.result.revenue != null ? Number(calc.result.revenue).toFixed(2) : '0.00')"></span></li>
                    <li><span class="font-bold text-muted">Cost (provider used):</span> <span class="font-black" x-text="'GHS ' + (calc.result.cost != null ? Number(calc.result.cost).toFixed(2) : '0.00')"></span></li>
                    <li><span class="font-bold text-green-600">Profit:</span> <span class="font-black text-green-700" x-text="'GHS ' + (calc.result.profit != null ? Number(calc.result.profit).toFixed(2) : '0.00')"></span></li>
                </ul>
            </div>
        </div>

        <!-- Daily breakdown -->
        <div class="glass rounded-card overflow-hidden border border-line/50 bg-surface shadow-none">
            <div class="p-6 border-b border-line">
                <h3 class="text-lg font-black text-brand-navy">Daily breakdown (orders &amp; profit)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-surface-muted border-b border-line">
                            <th class="px-6 py-4 text-xs font-black text-muted uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-xs font-black text-muted uppercase tracking-wider text-right">Orders</th>
                            <th class="px-6 py-4 text-xs font-black text-muted uppercase tracking-wider text-right">Amount spent</th>
                            <th class="px-6 py-4 text-xs font-black text-muted uppercase tracking-wider text-right">Profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in (data.daily_breakdown || [])" :key="row.date">
                            <tr class="border-b border-slate-50 hover:bg-surface-muted/50">
                                <td class="px-6 py-4 font-bold text-brand-navy" x-text="row.date"></td>
                                <td class="px-6 py-4 text-right font-black text-brand-navy" x-text="row.orders_count"></td>
                                <td class="px-6 py-4 text-right font-bold text-ink" x-text="'GHS ' + (row.amount_spent != null ? Number(row.amount_spent).toFixed(2) : '0.00')"></td>
                                <td class="px-6 py-4 text-right font-bold text-green-600" x-text="'GHS ' + (row.profit != null ? Number(row.profit).toFixed(2) : '0.00')"></td>
                            </tr>
                        </template>
                        <tr x-show="!(data.daily_breakdown && data.daily_breakdown.length)">
                            <td colspan="4" class="px-6 py-12 text-center text-muted font-bold">No data for selected period</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function financeStatistics() {
    return {
        data: {},
        error: '',
        dateFrom: '',
        dateTo: '',
        calc: {
            investedAmount: '',
            paystackTotal: '',
            userBalanceAtStart: '',
            startBalanceDate: '',
            providerBalanceNow: '',
            result: null
        },
        snapshotSaving: false,
        async init() {
            if (!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            const to = new Date();
            const from = new Date();
            from.setDate(from.getDate() - 30);
            this.dateTo = to.toISOString().slice(0, 10);
            this.dateFrom = from.toISOString().slice(0, 10);
            await this.fetchStats();
            await this.fetchSnapshot();
        },
        async fetchSnapshot() {
            try {
                const r = await fetch('/api/admin/profit-calc-snapshot', {
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' }
                });
                if (!r.ok) return;
                const j = await r.json();
                if (j.snapshot_balance != null) this.calc.userBalanceAtStart = String(j.snapshot_balance);
                if (j.snapshot_date) this.calc.startBalanceDate = j.snapshot_date;
            } catch (e) { console.error(e); }
        },
        doCalc() {
            const invested = parseFloat(this.calc.investedAmount) || 0;
            const paystack = parseFloat(this.calc.paystackTotal) || 0;
            const startBal = parseFloat(this.calc.userBalanceAtStart) || 0;
            const currentBal = this.data.total_users_balance != null ? parseFloat(this.data.total_users_balance) : 0;
            const providerBal = parseFloat(this.calc.providerBalanceNow) || 0;
            const revenue = paystack - (currentBal - startBal);
            const cost = invested - providerBal;
            const profit = revenue - cost;
            this.calc.result = { revenue, cost, profit };
        },
        async saveSnapshot() {
            const balance = this.data.total_users_balance != null ? this.data.total_users_balance : null;
            if (balance == null) return;
            this.snapshotSaving = true;
            try {
                const r = await fetch('/api/admin/profit-calc-snapshot', {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ balance: parseFloat(balance) })
                });
                if (!r.ok) { const e = await r.json().catch(() => ({})); this.error = e.error || 'Failed to save'; return; }
                const j = await r.json();
                this.calc.userBalanceAtStart = String(j.snapshot_balance);
                this.calc.startBalanceDate = j.snapshot_date;
            } catch (e) { console.error(e); this.error = 'Network error'; }
            this.snapshotSaving = false;
        },
        setLast30() {
            const to = new Date();
            const from = new Date();
            from.setDate(from.getDate() - 30);
            this.dateTo = to.toISOString().slice(0, 10);
            this.dateFrom = from.toISOString().slice(0, 10);
            this.applyFilter();
        },
        applyFilter() {
            this.fetchStats();
        },
        async fetchStats() {
            this.error = '';
            try {
                const params = new URLSearchParams();
                if (this.dateFrom) params.set('date_from', this.dateFrom);
                if (this.dateTo) params.set('date_to', this.dateTo);
                const url = '/api/admin/finance-statistics' + (params.toString() ? '?' + params.toString() : '');
                const response = await fetch(url, {
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' }
                });
                if (!response.ok) {
                    const err = await response.json().catch(() => ({}));
                    this.error = err.error || err.message || 'Failed to load statistics';
                    return;
                }
                this.data = await response.json();
            } catch (e) {
                console.error(e);
                this.error = 'Network error.';
            }
        }
    };
}
</script>
</x-layouts.admin>
