@extends('layouts.app')

@section('content')
<div class="bg-slate-50 py-24 min-h-screen">
    <div class="max-w-5xl mx-auto px-6">
        <div class="relative mb-16 overflow-hidden p-16 rounded-[48px] bg-slate-900 text-white shadow-3xl">
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-primary-500/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-96 h-96 bg-primary-500/10 rounded-full blur-3xl"></div>
            <div class="relative z-10 text-center">
                <h1 class="text-6xl font-black mb-6 italic tracking-tighter">Terms of <span class="text-primary-400">Service</span></h1>
                <p class="text-slate-400 text-lg font-bold tracking-widest uppercase">Agreement & Usage Guidelines</p>
                <div class="mt-8 inline-block px-6 py-2 bg-white/10 rounded-full text-sm font-bold backdrop-blur-md">Last updated: February 15, 2026</div>
            </div>
        </div>

        <div class="glass p-16 rounded-[48px] shadow-2xl border border-slate-200/50 -mt-24 relative z-20">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-16">
                <div class="lg:col-span-1 border-r border-slate-100 pr-10">
                    <h3 class="text-sm font-black text-primary-600 uppercase tracking-widest mb-6">Legal Menu</h3>
                    <ul class="space-y-4 font-bold text-slate-500">
                        <li class="hover:text-primary-600 cursor-pointer transition-colors">1. General Terms</li>
                        <li class="hover:text-primary-600 cursor-pointer transition-colors">2. Service Scope</li>
                        <li class="hover:text-primary-600 cursor-pointer transition-colors">3. Refund Policy</li>
                        <li class="hover:text-primary-600 cursor-pointer transition-colors">4. Liability Limits</li>
                    </ul>
                </div>
                <div class="lg:col-span-2 space-y-12">
                    <section>
                        <h2 class="text-2xl font-black text-slate-900 mb-4 tracking-tight">1. Acceptance of Terms</h2>
                        <p class="text-slate-500 font-medium leading-relaxed">By accessing or using R2BMarket, you agree to be bound by these Terms of Service and all applicable laws and regulations. If you do not agree with any of these terms, you are prohibited from using or accessing this site.</p>
                    </section>

                    <section class="p-8 bg-slate-900 text-white rounded-3xl shadow-xl">
                        <h2 class="text-2xl font-black mb-4 tracking-tight text-primary-400">2. Delivery Policy</h2>
                        <p class="text-slate-300 font-medium leading-relaxed">R2BMarket provides instant mobile data delivery. However, delivery times may vary due to network congestion or service provider downtime. We guarantee that your data will be delivered or your money will be refunded.</p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-black text-slate-900 mb-4 tracking-tight">3. Refund & Cancellation</h2>
                        <p class="text-slate-500 font-medium leading-relaxed">Due to the nature of digital data bundles, all sales are final once the data has been successfully sent to the recipient's phone number. Refunds are only applicable if there is a technical failure on our end that prevents delivery.</p>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
