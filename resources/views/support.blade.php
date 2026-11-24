<x-layouts.public title="Support">
<style>
.support-page { font-family: inherit; }
</style>
<div class="support-page min-h-screen bg-gray-50 py-8 md:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <header class="text-center mb-10">
            <p class="text-gray-500 text-sm font-semibold uppercase tracking-wider mb-1">Support</p>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Support Center</h1>
            <p class="mt-2 text-gray-500 font-medium">We're here to help. Reach us by email, WhatsApp, or send a message.</p>
        </header>

        <!-- Contact cards (optional row) -->
        @if(!empty($support_email) || !empty($support_whatsapp_url))
        <div class="flex flex-wrap justify-center gap-4 mb-8">
            @if(!empty($support_email))
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 min-w-[240px]">
                <div class="w-12 h-12 bg-line text-brand-navy rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Email Us</h3>
                <p class="text-gray-500 text-sm mb-3">We usually reply within 2 hours.</p>
                <a href="mailto:{{ e($support_email) }}" class="text-brand-navy font-semibold hover:underline text-sm">{{ e($support_email) }}</a>
            </div>
            @endif
            @if(!empty($support_whatsapp_url))
            <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 min-w-[240px]">
                <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">WhatsApp</h3>
                <p class="text-gray-500 text-sm mb-3">Chat with us instantly.</p>
                <a href="{{ e($support_whatsapp_url) }}" target="_blank" rel="noopener" class="text-green-600 font-semibold hover:underline text-sm">{{ $support_whatsapp_display ? e($support_whatsapp_display) : 'Chat on WhatsApp' }}</a>
            </div>
            @endif
        </div>
        @endif

        <!-- Support form (centered on page) -->
        <div class="max-w-xl mx-auto">
            <div class="bg-white p-6 md:p-8 rounded-xl shadow-sm border border-slate-200">
                <h2 class="text-lg font-bold text-gray-900 mb-6">Send us a message</h2>
                <form class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Subject</label>
                            <select class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-brand-navy font-medium text-gray-900 text-sm">
                                <option>General Inquiry</option>
                                <option>Top-up Issue</option>
                                <option>Bundle Purchase Error</option>
                                <option>Account Access</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Order ID (optional)</label>
                            <input type="text" placeholder="#12345" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-brand-navy font-medium text-gray-900 placeholder-gray-400 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Your message</label>
                        <textarea rows="5" placeholder="How can we help you?" class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:outline-none focus:border-brand-navy font-medium text-gray-900 placeholder-gray-400 resize-none text-sm"></textarea>
                    </div>
                    <button type="button" class="px-6 py-3 rounded-xl font-semibold bg-brand-navy text-white hover:bg-brand-navy transition-colors text-sm">Submit ticket</button>
                </form>
            </div>
        </div>
    </div>
</div>
</x-layouts.public>
