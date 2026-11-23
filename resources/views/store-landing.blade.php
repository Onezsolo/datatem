@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-16 flex items-center justify-center px-4" x-data="storeLanding()">
    <div class="max-w-lg w-full">
        <div class="text-center mb-10">
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">Visit a <span class="gradient-text">store</span></h1>
            <p class="mt-3 text-slate-500 font-medium">Enter your store code to buy data bundles. No account needed.</p>
        </div>
        <div class="bg-white rounded-[32px] shadow-xl border border-slate-200/50 p-8">
            <label class="block text-sm font-bold text-slate-600 mb-2">Store code</label>
            <p class="text-xs text-slate-500 mb-3">Your reseller gave you a link like yourdomain.com/store/<strong>iksoft</strong> — enter the part after /store/ (e.g. iksoft).</p>
            <input type="text" x-model="storeCode" placeholder="e.g. r5" @keydown.enter="go()"
                   class="w-full px-6 py-4 rounded-2xl border border-slate-200 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 outline-none font-bold text-slate-900 placeholder-slate-400">
            <p x-show="errorMsg" class="mt-2 text-sm font-bold text-red-600" x-text="errorMsg"></p>
            <button type="button" @click="go()"
                    class="mt-6 w-full py-4 rounded-2xl font-black bg-primary-600 text-white hover:bg-primary-700 transition-all">
                Go to store
            </button>
        </div>
    </div>
</div>

<script>
function storeLanding() {
    return {
        storeCode: '',
        errorMsg: '',
        go() {
            this.errorMsg = '';
            const slug = (this.storeCode || '').trim().replace(/^\/+|\/+$/g, '');
            if (!slug) {
                this.errorMsg = 'Please enter a store code.';
                return;
            }
            window.location.href = '/store/' + encodeURIComponent(slug);
        }
    };
}
</script>
@endsection
