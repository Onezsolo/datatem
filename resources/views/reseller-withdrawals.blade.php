@extends('layouts.app')
@section('title', 'Reseller withdrawals')
@section('content')
<div class="min-h-screen bg-gray-50 py-8 md:py-12 font-[Outfit,sans-serif]" x-data="resellerWithdrawalsPage()" x-init="init()">
    <div class="max-w-lg mx-auto px-4">
        <a href="/reseller" class="text-cyan-600 font-semibold hover:text-cyan-700 mb-6 inline-flex items-center text-sm">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to reseller
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Withdraw earnings</h1>
        <p class="text-gray-500 text-sm mb-6">Withdrawals use only <strong>store profit</strong> credited to your wallet. Top-ups stay in your main balance for buying data and are not paid out here.</p>

        <div x-show="errorMsg" class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm font-semibold" x-text="errorMsg"></div>
        <div x-show="successMsg" class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-semibold" x-text="successMsg"></div>

        <template x-if="!allowed">
            <div class="bg-white rounded-xl border border-slate-200 p-6 text-center text-gray-600 text-sm">
                <p x-text="gateMessage"></p>
            </div>
        </template>

        <template x-if="allowed">
            <div class="space-y-6">
                <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
                    <div>
                        <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wider mb-1">Withdrawable (store profit)</p>
                        <p class="text-2xl font-bold text-emerald-700">GHS <span x-text="Number(withdrawable).toFixed(2)"></span></p>
                    </div>
                    <div class="pt-2 border-t border-slate-100">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Full wallet (incl. top-ups)</p>
                        <p class="text-lg font-bold text-gray-800">GHS <span x-text="Number(balance).toFixed(2)"></span></p>
                    </div>
                    <p class="text-xs text-gray-500">Minimum withdrawal: GHS <span x-text="Number(minWithdraw).toFixed(2)"></span></p>
                    <p x-show="hasPending" class="text-amber-700 text-sm font-semibold mt-3">You have a pending request. Wait for it to be processed before submitting another.</p>
                </div>

                <div x-show="!hasPending" class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-2">Amount (GHS)</label>
                        <input type="number" step="0.01" min="0" x-model.number="amount" class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-cyan-500 outline-none font-medium text-gray-900">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-2">Mobile money number</label>
                        <input type="text" inputmode="tel" autocomplete="tel" x-model="momoNumber" placeholder="e.g. 0241234567 or 233241234567"
                               class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-cyan-500 outline-none font-medium text-gray-900">
                        <p class="text-xs text-gray-500 mt-1.5">Ghana MoMo: use your registered number (0XXXXXXXXX or 233XXXXXXXXX).</p>
                    </div>
                    <button type="button" @click="submit()" :disabled="submitting" class="w-full py-3 rounded-xl font-semibold bg-cyan-500 text-white hover:bg-cyan-600 disabled:opacity-50 text-sm">
                        <span x-text="submitting ? 'Submitting…' : 'Request withdrawal'"></span>
                    </button>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-100 font-bold text-gray-900">History</div>
                    <div class="divide-y divide-slate-100 max-h-96 overflow-y-auto">
                        <template x-for="w in withdrawals" :key="w.id">
                            <div class="p-4 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="font-bold text-gray-900">GHS <span x-text="Number(w.amount).toFixed(2)"></span></p>
                                    <p class="text-xs text-gray-500" x-text="w.created_at ? new Date(w.created_at).toLocaleString() : ''"></p>
                                    <p x-show="w.momo_number" class="text-xs font-medium text-gray-700 mt-1">MoMo: <span x-text="w.momo_number"></span></p>
                                    <p x-show="w.admin_notes && w.status === 'rejected'" class="text-xs text-red-600 mt-1" x-text="w.admin_notes"></p>
                                </div>
                                <span class="text-xs font-bold uppercase px-2 py-1 rounded-lg"
                                      :class="{
                                          'bg-amber-100 text-amber-800': w.status === 'pending',
                                          'bg-emerald-100 text-emerald-800': w.status === 'approved',
                                          'bg-red-100 text-red-800': w.status === 'rejected'
                                      }"
                                      x-text="w.status"></span>
                            </div>
                        </template>
                        <div x-show="withdrawals.length === 0" class="p-8 text-center text-gray-500 text-sm">No withdrawal requests yet.</div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
<script>
function resellerWithdrawalsPage() {
    return {
        allowed: false,
        gateMessage: 'Loading…',
        balance: 0,
        withdrawable: 0,
        minWithdraw: 50,
        hasPending: false,
        withdrawals: [],
        amount: '',
        momoNumber: '',
        submitting: false,
        errorMsg: '',
        successMsg: '',

        async init() {
            if (!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            await this.load();
        },

        async load() {
            this.errorMsg = '';
            try {
                const res = await fetch('/api/auth/reseller', {
                    headers: { 'Authorization': 'Bearer ' + this.$store.auth.token, 'Accept': 'application/json' }
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.gateMessage = data.error || 'Could not load reseller data.';
                    this.allowed = false;
                    return;
                }
                const req = data.request;
                if (!req || req.status !== 'approved') {
                    this.allowed = false;
                    this.gateMessage = 'You need an approved reseller account to withdraw.';
                    return;
                }
                if (!data.store_setup_complete) {
                    this.allowed = false;
                    this.gateMessage = 'Complete your store setup on the reseller page first.';
                    return;
                }
                this.allowed = true;
                this.balance = typeof data.wallet_balance === 'number' ? data.wallet_balance : parseFloat(data.wallet_balance) || 0;
                this.withdrawable = typeof data.reseller_withdrawable_balance === 'number'
                    ? data.reseller_withdrawable_balance
                    : parseFloat(data.reseller_withdrawable_balance) || 0;
                this.minWithdraw = typeof data.min_withdraw === 'number' ? data.min_withdraw : parseFloat(data.min_withdraw) || 50;
                this.hasPending = data.has_pending_withdrawal === true;
                this.withdrawals = Array.isArray(data.withdrawals) ? data.withdrawals : [];
            } catch (e) {
                this.allowed = false;
                this.gateMessage = 'Something went wrong.';
            }
        },

        async submit() {
            this.errorMsg = '';
            this.successMsg = '';
            const amt = parseFloat(this.amount);
            if (!amt || amt < this.minWithdraw) {
                this.errorMsg = 'Enter an amount of at least GHS ' + this.minWithdraw.toFixed(2) + '.';
                return;
            }
            if (amt > this.withdrawable) {
                this.errorMsg = 'Amount exceeds your withdrawable store profit (GHS ' + this.withdrawable.toFixed(2) + '). Top-up balance cannot be withdrawn.';
                return;
            }
            const momo = (this.momoNumber || '').trim();
            if (!momo) {
                this.errorMsg = 'Enter the mobile money number to receive the payout.';
                return;
            }
            this.submitting = true;
            try {
                const res = await fetch('/api/auth/reseller/withdraw', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ amount: amt, momo_number: momo })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    this.errorMsg = data.errors?.momo_number?.[0] || data.errors?.amount?.[0] || data.error || data.message || 'Request failed.';
                    return;
                }
                this.successMsg = data.message || 'Request submitted.';
                this.amount = '';
                this.momoNumber = '';
                await this.load();
            } catch (e) {
                this.errorMsg = 'Something went wrong.';
            } finally {
                this.submitting = false;
            }
        }
    };
}
</script>
@endsection
