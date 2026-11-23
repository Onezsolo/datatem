@extends('layouts.app')

@section('content')
<div class="min-h-[calc(100vh-80px)] flex items-center justify-center p-6 bg-slate-50" x-data="{
    full_name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    ref: new URLSearchParams(window.location.search).get('ref') || '',
    loading: false,
    errors: {},
    async signup() {
        this.loading = true;
        this.errors = {};
        try {
            const payload = {
                full_name: this.full_name,
                email: this.email,
                phone: this.phone,
                password: this.password,
                password_confirmation: this.password_confirmation
            };
            if (this.ref) payload.ref = this.ref;
            const response = await fetch('/api/auth/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await response.json();
            if (response.ok) {
                this.$store.auth.login(data.user, data.authorization.token);
                window.location.href = '/dashboard';
            } else {
                if (typeof data === 'object' && data !== null && !Array.isArray(data)) {
                    this.errors = {};
                    for (const [k, v] of Object.entries(data)) {
                        this.errors[k] = Array.isArray(v) ? v : [v];
                    }
                    if (data.error && !this.errors.general) this.errors.general = [data.error];
                } else {
                    this.errors = { general: [data.error || 'Server error. Please try again.'] };
                }
            }
        } catch (e) {
            console.error('Signup Error:', e);
            this.errors = { general: ['Could not connect to server. Please check your connection.'] };
        } finally {
            this.loading = false;
        }
    }
}">
    <div class="max-w-md w-full">
        <div class="glass p-10 rounded-[40px] shadow-2xl border border-slate-200/50">
            <div class="text-center mb-10">
                <img src="{{ asset('assets/l1.png') }}" alt="R2BMarket" class="h-20 w-auto object-contain mx-auto">
                <h2 class="mt-6 text-2xl font-black text-slate-900">Join R2BMarket</h2>
                <p class="mt-2 text-slate-500 font-medium">Start saving on your data bundles today</p>
            </div>
            
            <div x-show="Object.keys(errors).length > 0" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-2xl text-sm font-bold">
                <template x-if="errors.error">
                    <p x-text="errors.error"></p>
                </template>
                <template x-if="!errors.error">
                    <ul class="list-disc list-inside">
                        <template x-for="(messages, field) in errors">
                            <template x-if="Array.isArray(messages)">
                                <template x-for="message in messages">
                                    <li x-text="message"></li>
                                </template>
                            </template>
                        </template>
                    </ul>
                </template>
            </div>

            <form @submit.prevent="signup" class="space-y-5">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Full Name</label>
                    <input type="text" x-model="full_name" required placeholder="John Doe" class="w-full px-6 py-4 rounded-2xl bg-white border border-slate-200 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 outline-none transition-all font-medium">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Email Address</label>
                    <input type="email" x-model="email" required placeholder="name@example.com" class="w-full px-6 py-4 rounded-2xl bg-white border border-slate-200 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 outline-none transition-all font-medium">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Phone Number</label>
                    <input type="tel" x-model="phone" required placeholder="024XXXXXXX" class="w-full px-6 py-4 rounded-2xl bg-white border border-slate-200 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 outline-none transition-all font-medium">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Password</label>
                        <input type="password" x-model="password" required placeholder="••••••••" class="w-full px-6 py-4 rounded-2xl bg-white border border-slate-200 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 outline-none transition-all font-medium">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Confirm</label>
                        <input type="password" x-model="password_confirmation" required placeholder="••••••••" class="w-full px-6 py-4 rounded-2xl bg-white border border-slate-200 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 outline-none transition-all font-medium">
                    </div>
                </div>

                <div class="flex items-start space-x-2 py-2">
                    <input type="checkbox" required class="mt-1 w-5 h-5 rounded-lg border-slate-300 text-cyan-600 focus:ring-cyan-500">
                    <span class="text-sm text-slate-600 font-medium">I agree to the <a href="/terms" class="text-cyan-600 font-bold hover:underline">Terms</a> and <a href="/privacy" class="text-cyan-600 font-bold hover:underline">Privacy Policy</a></span>
                </div>

                <button type="submit" :disabled="loading" class="w-full bg-cyan-600 text-white font-black py-4 rounded-2xl transition-all hover:bg-cyan-700 active:scale-[0.98] shadow-xl shadow-cyan-500/30 flex items-center justify-center space-x-3 disabled:opacity-50">
                    <span x-show="!loading">Create Account</span>
                    <span x-show="loading" class="flex items-center">
                        <svg class="animate-spin h-5 w-5 text-white mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Creating account...
                    </span>
                </button>
            </form>

            <div class="mt-10 text-center">
                <p class="text-slate-500 font-medium">
                    Already have an account? 
                    <a href="/login" class="text-cyan-600 font-black hover:text-cyan-700">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
