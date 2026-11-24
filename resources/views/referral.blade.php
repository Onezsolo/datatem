<x-layouts.member title="Referral">
<style>
.referral-page { font-family: inherit; }
</style>
<div class="referral-page min-h-screen bg-gray-50 py-8 md:py-12" x-data="referralPage()" x-init="init()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <a href="/dashboard" class="text-brand-navy font-semibold hover:text-brand-navy-soft mb-6 inline-flex items-center text-sm">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Dashboard
        </a>

        <header class="mb-8">
            <p class="text-gray-500 text-sm font-semibold uppercase tracking-wider mb-1">Referral</p>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Invite & Earn</h1>
            <p class="mt-1 text-gray-500 font-medium">Share your link. Earn when referred users sign up and buy data.</p>
        </header>

        <template x-if="loading && !affiliate && !error">
            <div class="bg-white p-12 rounded-xl border border-slate-200 shadow-sm text-center">
                <div class="animate-spin w-10 h-10 border-2 border-brand-navy border-t-transparent rounded-full mx-auto"></div>
                <p class="mt-4 font-medium text-gray-500 text-sm">Loading...</p>
            </div>
        </template>

        <template x-if="error && !loading">
            <div class="bg-white p-6 rounded-xl border border-red-200 shadow-sm bg-red-50/50">
                <p class="font-semibold text-red-700 text-sm" x-text="error"></p>
            </div>
        </template>

        <template x-if="!loading && !error && affiliate === null && message">
            <div class="bg-white p-8 rounded-xl border border-slate-200 shadow-sm text-center">
                <p class="text-gray-600 font-medium text-sm" x-text="message"></p>
                <p class="mt-3 text-sm text-gray-500">Contact support to join the affiliate program.</p>
            </div>
        </template>

        <template x-if="!loading && !error && affiliate">
            <div class="space-y-6">
                <!-- Referral link -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-base font-bold text-gray-900 mb-4">Your referral link</h2>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input type="text" readonly :value="referralUrl" class="flex-1 min-w-0 px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 font-mono text-sm font-medium text-gray-900">
                            <button type="button" @click="copyCode()" class="px-5 py-3 rounded-xl font-semibold bg-brand-navy text-white hover:bg-brand-navy transition-colors text-sm shrink-0">
                                <span x-text="copied ? 'Copied!' : 'Copy link'"></span>
                            </button>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">Share this link. You earn a signup bonus and commission on referred users’ purchases.</p>
                    </div>
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 p-6 pt-0">
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 min-w-[160px]">
                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Referrals</p>
                            <p class="text-xl font-bold text-gray-900 mt-0.5 whitespace-nowrap" x-text="affiliate.referrals_count != null ? Number(affiliate.referrals_count) : 0"></p>
                            <p class="text-[10px] text-gray-500 mt-0.5">signed up</p>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 min-w-[160px]">
                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Balance</p>
                            <p class="text-xl font-bold text-brand-navy mt-0.5 whitespace-nowrap" x-text="'GH₵ ' + (affiliate.balance != null ? Number(affiliate.balance).toFixed(2) : '0.00')"></p>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 min-w-[160px]">
                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Earned</p>
                            <p class="text-xl font-bold text-gray-900 mt-0.5 whitespace-nowrap" x-text="'GH₵ ' + (affiliate.total_earned != null ? Number(affiliate.total_earned).toFixed(2) : '0.00')"></p>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 min-w-[160px]">
                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Withdrawn</p>
                            <p class="text-xl font-bold text-gray-900 mt-0.5 whitespace-nowrap" x-text="'GH₵ ' + (affiliate.total_withdrawn != null ? Number(affiliate.total_withdrawn).toFixed(2) : '0.00')"></p>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-100 min-w-[160px] col-span-2 lg:col-span-1 flex flex-col justify-center">
                            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Status</p>
                            <span class="inline-flex mt-0.5 px-2.5 py-1 rounded-lg text-xs font-semibold w-fit" :class="affiliate.status === 'approved' ? 'bg-emerald-100 text-emerald-700' : (affiliate.status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')" x-text="affiliate.status"></span>
                        </div>
                    </div>
                </div>

                <!-- Request withdrawal -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6" x-show="affiliate.status === 'approved'">
                    <h2 class="text-base font-bold text-gray-900 mb-1">Request withdrawal</h2>
                    <p class="text-sm text-gray-500 mb-4">Minimum GH₵ <span x-text="min_withdraw != null ? Number(min_withdraw).toFixed(2) : '50.00'"></span>. A fee may apply.</p>
                    <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                        <div class="flex-1 min-w-0">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Amount (GH₵)</label>
                            <input type="number" x-model="withdrawAmount" step="0.01" :min="min_withdraw" placeholder="0.00" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-brand-navy font-medium text-gray-900 text-sm">
                            <p class="mt-1 text-xs text-red-600" x-show="withdrawError" x-text="withdrawError"></p>
                        </div>
                        <button type="button" @click="submitWithdraw()" :disabled="withdrawLoading" class="px-5 py-3 rounded-xl font-semibold bg-brand-navy text-white hover:bg-brand-navy disabled:opacity-50 transition-colors text-sm shrink-0">
                            <span x-text="withdrawLoading ? 'Submitting...' : 'Submit request'"></span>
                        </button>
                    </div>
                </div>

                <!-- Withdrawal history -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-200">
                        <h2 class="text-base font-bold text-gray-900">Withdrawal history</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 border-b border-slate-200">
                                <tr>
                                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Fee</th>
                                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="w in withdrawals" :key="w.id">
                                    <tr class="border-b border-slate-100 hover:bg-slate-50/80">
                                        <td class="px-5 py-3 font-semibold text-gray-900 text-sm" x-text="'GH₵ ' + (w.amount != null ? Number(w.amount).toFixed(2) : '0.00')"></td>
                                        <td class="px-5 py-3 text-sm text-gray-600" x-text="w.fee_amount != null ? ('GH₵ ' + Number(w.fee_amount).toFixed(2)) : '—'"></td>
                                        <td class="px-5 py-3">
                                            <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-semibold" :class="w.status === 'approved' ? 'bg-emerald-100 text-emerald-700' : (w.status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')" x-text="w.status"></span>
                                        </td>
                                        <td class="px-5 py-3 text-sm text-gray-500" x-text="w.created_at ? new Date(w.created_at).toLocaleDateString() : '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <p x-show="withdrawals.length === 0" class="py-10 text-center text-gray-500 font-medium text-sm">No withdrawals yet.</p>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function referralPage() {
    return {
        loading: true,
        error: '',
        message: '',
        affiliate: null,
        withdrawals: [],
        min_withdraw: 50,
        referralUrl: '',
        withdrawAmount: '',
        withdrawError: '',
        withdrawLoading: false,
        copied: false,

        async init() {
            if (!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            await this.fetchData();
        },

        async fetchData() {
            this.loading = true;
            this.error = '';
            try {
                const r = await fetch('{{ url("/api/auth/affiliate") }}', {
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' }
                });
                const data = await r.json().catch(() => ({}));
                if (r.ok) {
                    this.affiliate = data.affiliate || null;
                    this.withdrawals = data.withdrawals || [];
                    this.min_withdraw = data.min_withdraw != null ? Number(data.min_withdraw) : 50;
                    this.message = data.message || '';
                    if (this.affiliate && this.affiliate.referral_code) {
                        this.referralUrl = window.location.origin + '/signup?ref=' + encodeURIComponent(this.affiliate.referral_code);
                    }
                } else {
                    this.error = data.error || 'Failed to load referral data.';
                }
            } catch (e) {
                this.error = 'Could not connect. Please try again.';
            } finally {
                this.loading = false;
            }
        },

        copyCode() {
            if (!this.referralUrl) return;
            navigator.clipboard.writeText(this.referralUrl).then(() => {
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            });
        },

        async submitWithdraw() {
            this.withdrawError = '';
            const amt = parseFloat(this.withdrawAmount);
            if (isNaN(amt) || amt < this.min_withdraw) {
                this.withdrawError = 'Amount must be at least GH₵ ' + this.min_withdraw.toFixed(2);
                return;
            }
            if (this.affiliate && amt > parseFloat(this.affiliate.balance || 0)) {
                this.withdrawError = 'Insufficient balance.';
                return;
            }
            this.withdrawLoading = true;
            try {
                const r = await fetch('{{ url("/api/auth/affiliate/withdraw") }}', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ amount: amt })
                });
                const data = await r.json().catch(() => ({}));
                if (r.ok) {
                    this.withdrawAmount = '';
                    await this.fetchData();
                    this.$store.auth.refreshUser && this.$store.auth.refreshUser();
                } else {
                    this.withdrawError = data.error || 'Request failed.';
                }
            } catch (e) {
                this.withdrawError = 'Network error. Try again.';
            } finally {
                this.withdrawLoading = false;
            }
        }
    };
}
</script>
</x-layouts.member>
