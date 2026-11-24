<x-layouts.public title="Contact">
<div class="bg-slate-50 py-20 min-h-screen">
    <div class="max-w-4xl mx-auto px-6">
        <div class="glass p-12 rounded-[40px] shadow-2xl border border-slate-200/50">
            <h1 class="text-4xl font-black text-slate-900 mb-8">Contact Us</h1>
            <p class="text-xl text-slate-500 font-medium mb-12 text-center">Have questions? We're here to help you 24/7.</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <div class="space-y-8">
                    <div class="flex items-start space-x-6">
                        <div class="bg-primary-100 p-4 rounded-2xl text-primary-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Email Us</h3>
                            <p class="text-slate-500 font-medium">support@r2bmarket.com</p>
                        </div>
                    </div>
                    
                    <div class="flex items-start space-x-6">
                        <div class="bg-green-100 p-4 rounded-2xl text-green-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">WhatsApp</h3>
                            <p class="text-slate-500 font-medium">+233 24 000 0000</p>
                        </div>
                    </div>
                </div>

                <form action="#" class="space-y-6">
                    <div>
                        <input type="text" placeholder="Your Name" class="w-full px-6 py-4 rounded-2xl bg-white border border-slate-200 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 outline-none transition-all font-medium">
                    </div>
                    <div>
                        <input type="email" placeholder="Your Email" class="w-full px-6 py-4 rounded-2xl bg-white border border-slate-200 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 outline-none transition-all font-medium">
                    </div>
                    <div>
                        <textarea rows="4" placeholder="How can we help?" class="w-full px-6 py-4 rounded-2xl bg-white border border-slate-200 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 outline-none transition-all font-medium"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-primary-600 text-white font-black py-4 rounded-2xl transition-all hover:bg-primary-700 active:scale-[0.98] shadow-xl shadow-primary-500/30">
                        Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</x-layouts.public>
