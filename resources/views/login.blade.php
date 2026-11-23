@extends('layouts.app')

@section('content')
<div class="min-h-[calc(100vh-80px)] flex items-center justify-center p-6 bg-slate-50" x-data="{
    login_field: '',
    password: '',
    keep_me_signed_in: true,
    loading: false,
    error: '',
    async login() {
        this.loading = true;
        this.error = '';
        try {
            const response = await fetch('/api/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ login: this.login_field, password: this.password })
            });
            const data = await response.json();
            if (response.ok) {
                this.$store.auth.login(data.user, data.access_token, this.keep_me_signed_in);
                window.location.href = data.user.role === 'admin' ? '/admin/dashboard' : '/dashboard';
            } else {
                this.error = data.error || 'Invalid credentials';
            }
        } catch (e) {
            this.error = 'An error occurred. Please try again.';
        } finally {
            this.loading = false;
        }
    }
}">
    <div class="max-w-md w-full">
        <div class="glass p-10 rounded-[40px] shadow-2xl border border-slate-200/50">
            <div class="text-center mb-10">
                <img src="{{ asset('assets/l1.png') }}" alt="R2BMarket" class="h-20 w-auto object-contain mx-auto">
                <h2 class="mt-6 text-2xl font-black text-slate-900">Welcome Back</h2>
                <p class="mt-2 text-slate-500 font-medium">Log in to manage your data bundles</p>
            </div>
            
            <div x-show="error" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-2xl text-sm font-bold" x-text="error"></div>

            <form @submit.prevent="login" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Email or Phone Number</label>
                    <input type="text" x-model="login_field" required placeholder="024XXXXXXX or name@example.com" class="w-full px-6 py-4 rounded-2xl bg-white border border-slate-200 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 outline-none transition-all font-medium">
                </div>
                
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="block text-sm font-bold text-slate-700">Password</label>
                        <a href="/forgot-password" class="text-xs font-bold text-cyan-600 hover:underline">Forgot?</a>
                    </div>
                    <input type="password" x-model="password" required placeholder="••••••••" class="w-full px-6 py-4 rounded-2xl bg-white border border-slate-200 focus:border-cyan-500 focus:ring-4 focus:ring-cyan-500/10 outline-none transition-all font-medium">
                </div>

                <div class="flex items-center space-x-2 py-2">
                    <input type="checkbox" x-model="keep_me_signed_in" class="w-5 h-5 rounded-lg border-slate-300 text-cyan-600 focus:ring-cyan-500">
                    <span class="text-sm text-slate-600 font-medium">Keep me signed in</span>
                </div>

                <button type="submit" :disabled="loading" class="w-full bg-cyan-600 text-white font-black py-4 rounded-2xl transition-all hover:bg-cyan-700 active:scale-[0.98] shadow-xl shadow-cyan-500/30 flex items-center justify-center space-x-3 disabled:opacity-50">
                    <span x-show="!loading">Sign In</span>
                    <span x-show="loading" class="flex items-center">
                        <svg class="animate-spin h-5 w-5 text-white mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Logging in...
                    </span>
                </button>
            </form>

            <div class="mt-10 text-center">
                <p class="text-slate-500 font-medium">
                    New to R2BMarket? 
                    <a href="/signup" class="text-cyan-600 font-black hover:text-cyan-700">Create account</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
