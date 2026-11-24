<x-layouts.member title="Top Up">
<style>
.topup-page { font-family: inherit; }
.topup-page .amount-btn { background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569; font-weight: 600; transition: all 0.2s; }
.topup-page .amount-btn:hover { background: #e2e8f0; color: #0f172a; }
.topup-page .amount-btn.active { background: var(--color-brand-navy); border-color: var(--color-brand-navy); color: white; }
.topup-page .method-card { border: 2px solid #e2e8f0; transition: all 0.2s; }
.topup-page .method-card:hover { border-color: #cbd5e1; }
.topup-page .method-card.selected { border-color: var(--color-brand-navy); background: rgba(0, 21, 52, 0.06); }
</style>
<div class="topup-page min-h-screen bg-surface-muted py-8 md:py-12" x-data="topupPage()" x-init="init()">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <header class="text-center mb-8">
            <p class="text-muted text-sm font-semibold uppercase tracking-wider mb-1">Top Up</p>
            <h1 class="text-2xl md:text-3xl font-bold text-brand-navy">Add funds to your wallet</h1>
            <p class="mt-2 text-muted font-medium">Pay with MoMo or manual deposit. Min GHS 5.</p>
        </header>

        <div x-show="successMsg" x-transition class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-card font-semibold text-sm" x-text="successMsg"></div>
        <div x-show="errorMsg" x-transition class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-card font-semibold text-sm" x-text="errorMsg"></div>

        <div class="bg-surface rounded-card border border-line shadow-none overflow-hidden">
            <div class="p-6 md:p-8">
                <!-- Amount -->
                <div class="mb-8">
                    <h3 class="text-sm font-semibold text-muted uppercase tracking-wider mb-3">1. Amount</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-4">
                        <button @click="amount = 5" :class="amount === 5 ? 'amount-btn active' : 'amount-btn'" class="py-3 rounded-card text-sm">GHS 5</button>
                        <button @click="amount = 20" :class="amount === 20 ? 'amount-btn active' : 'amount-btn'" class="py-3 rounded-card text-sm">GHS 20</button>
                        <button @click="amount = 50" :class="amount === 50 ? 'amount-btn active' : 'amount-btn'" class="py-3 rounded-card text-sm">GHS 50</button>
                        <button @click="amount = 100" :class="amount === 100 ? 'amount-btn active' : 'amount-btn'" class="py-3 rounded-card text-sm">GHS 100</button>
                        <button @click="amount = 0" :class="amount === 0 ? 'amount-btn active' : 'amount-btn'" class="py-3 rounded-card text-sm col-span-2 sm:col-span-1">Custom</button>
                    </div>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-muted font-semibold">GHS</span>
                        <input type="number" x-model="amount" min="5" step="0.01" placeholder="Min 5" class="w-full pl-14 pr-4 py-3 rounded-card bg-surface-muted border border-line focus:outline-none focus:border-brand-navy text-brand-navy font-semibold">
                    </div>
                    <p class="mt-1.5 text-xs text-muted">Minimum GHS 5.00</p>
                </div>

                <!-- Payment method -->
                <div class="mb-8">
                    <h3 class="text-sm font-semibold text-muted uppercase tracking-wider mb-3">2. Payment method</h3>
                    <div class="space-y-3">
                        <label class="flex items-center p-4 rounded-card cursor-pointer method-card" :class="method === 'automated' ? 'selected' : ''">
                            <input type="radio" name="payment" x-model="method" value="automated" class="w-4 h-4 text-brand-navy border-slate-300 focus:ring-brand-navy">
                            <div class="ml-4 flex items-center gap-3">
                                <div class="w-10 h-10 bg-surface-muted rounded-lg flex items-center justify-center text-muted">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-brand-navy">Instant MoMo</p>
                                    <p class="text-xs text-muted">Pay with Paystack</p>
                                </div>
                            </div>
                        </label>
                        @if(!empty($manual_topup_enabled))
                        <label class="flex items-center p-4 rounded-card cursor-pointer method-card" :class="method === 'manual' ? 'selected' : ''">
                            <input type="radio" name="payment" x-model="method" value="manual" class="w-4 h-4 text-brand-navy border-slate-300 focus:ring-brand-navy">
                            <div class="ml-4 flex items-center gap-3">
                                <div class="w-10 h-10 bg-surface-muted rounded-lg flex items-center justify-center text-muted">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-brand-navy">Manual deposit</p>
                                    <p class="text-xs text-muted">Send & submit reference</p>
                                </div>
                            </div>
                        </label>
                        @endif
                    </div>
                </div>

                <!-- Manual instructions -->
                <div x-show="method === 'manual'" x-transition x-cloak class="mb-8 p-6 bg-surface-muted rounded-card border border-line">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-line text-brand-navy rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h4 class="font-bold text-brand-navy">Deposit instructions</h4>
                    </div>
                    <p class="text-muted text-sm mb-4">Send <span class="font-bold text-brand-navy" x-text="'GHS ' + amount"></span> to the details below, then enter your transaction reference.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div class="p-4 bg-surface rounded-lg border border-line">
                            <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-1">Number</p>
                            <p class="text-lg font-bold text-brand-navy" x-text="manualDetails.number"></p>
                        </div>
                        <div class="p-4 bg-surface rounded-lg border border-line">
                            <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-1">Name</p>
                            <p class="text-lg font-bold text-brand-navy" x-text="manualDetails.name"></p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-muted uppercase tracking-wider mb-2">Transaction ID / Reference <span class="text-amber-600">(required)</span></label>
                        <input type="text" x-model="manualReference" placeholder="Enter reference from your payment" class="w-full px-4 py-3 rounded-card bg-surface border border-line focus:outline-none focus:border-brand-navy text-brand-navy font-medium placeholder-gray-400">
                    </div>
                </div>

                <!-- Summary (automated) -->
                <div x-show="method === 'automated'" class="mb-8 p-5 bg-surface-muted rounded-card border border-line">
                    <p class="text-xs font-semibold text-muted uppercase tracking-wider mb-3">Summary</p>
                    <div class="flex justify-between text-sm text-muted mb-1"><span>Amount to wallet</span><span x-text="'GHS ' + (parseFloat(amount) || 0).toFixed(2)"></span></div>
                    <div class="flex justify-between text-sm text-muted mb-1"><span>Fee (4%)</span><span x-text="'GHS ' + (((parseFloat(amount) || 0) * 0.04).toFixed(2))"></span></div>
                    <div class="flex justify-between font-bold text-brand-navy pt-3 mt-2 border-t border-line">
                        <span>Total to pay</span>
                        <span class="text-brand-navy" x-text="'GHS ' + (((parseFloat(amount) || 0) * 1.04).toFixed(2))"></span>
                    </div>
                </div>

                <div class="pt-6 border-t border-line">
                    <button @click="method === 'automated' ? startPaystack() : submitManual()"
                            :disabled="loading || (method === 'manual' && !manualReference.trim())"
                            class="w-full py-3.5 rounded-card bg-brand-navy text-white font-semibold hover:bg-brand-navy disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <span x-show="!loading" x-text="method === 'automated' ? 'Pay with MoMo' : (manualReference.trim() ? 'Submit reference' : 'Enter reference above')"></span>
                        <span x-show="loading" class="inline-flex items-center justify-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Redirecting...
                        </span>
                    </button>
                    <p class="mt-4 text-muted text-xs font-medium flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span x-text="method === 'automated' ? 'Secure payment by Paystack' : 'Manual verification by admin'"></span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function topupPage() {
    return {
        method: 'automated',
        amount: 5,
        loading: false,
        successMsg: '',
        errorMsg: '',
        manualReference: '',
        manualDetails: { number: @json($manual_payment_number ?? '0241234567'), name: @json($manual_payment_name ?? 'Datatem Official') },
        init() {
            const params = new URLSearchParams(window.location.search);
            const success = params.get('success');
            const error = params.get('error');
            const amount = params.get('amount');
            if (success === '1') {
                this.successMsg = amount ? 'Your wallet has been credited with GHS ' + amount + '. Thank you!' : 'Payment successful. Your wallet has been credited.';
                history.replaceState({}, '', window.location.pathname);
            }
            if (error) {
                this.errorMsg = decodeURIComponent(error).replace(/\+/g, ' ');
                history.replaceState({}, '', window.location.pathname);
            }
        },
        async startPaystack() {
            if (!this.$store.auth.token) {
                window.location.href = '/login';
                return;
            }
            const amt = parseFloat(this.amount) || 0;
            if (amt < 5) {
                this.errorMsg = 'Minimum deposit is GHS 5.00.';
                return;
            }
            this.loading = true;
            this.errorMsg = '';
            this.successMsg = '';
            try {
                const response = await fetch('{{ url("/api/auth/topup/initialize") }}', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.$store.auth.token,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ amount: amt })
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const err = (data.errors && (data.errors.amount?.[0] || data.errors.email?.[0])) || data.message;
                    this.errorMsg = err || 'Could not start payment.';
                    return;
                }
                if (data.authorization_url) {
                    window.location.href = data.authorization_url;
                    return;
                }
                this.errorMsg = 'Invalid response. Please try again.';
            } catch (e) {
                console.error(e);
                this.errorMsg = 'Network error. Please try again.';
            } finally {
                this.loading = false;
            }
        },
        submitManual() {
            this.errorMsg = '';
            if (!this.manualReference || !this.manualReference.trim()) {
                this.errorMsg = 'Please enter your transaction ID or reference number before submitting.';
                return;
            }
            this.successMsg = 'Thank you. We received your reference: ' + this.manualReference.trim() + '. Please allow time for admin to verify and credit your wallet. You can contact support if needed.';
            this.manualReference = '';
        }
    };
}
</script>
</x-layouts.member>
