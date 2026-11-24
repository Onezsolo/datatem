<x-layouts.public title="Forgot password" robots="noindex,follow">
<div class="min-h-[calc(100vh-80px)] flex items-center justify-center p-6 bg-slate-50" x-data="{
    email: '',
    loading: false,
    message: '',
    error: '',
    async reset() {
        this.loading = true;
        this.message = '';
        this.error = '';
        // Mocking password reset for frontend demonstration
        setTimeout(() => {
            this.loading = false;
            this.message = 'If an account exists for ' + this.email + ', you will receive a password reset link shortly.';
        }, 1500);
    }
}">
    <div class="max-w-md w-full">
        <div class="glass p-10 rounded-[40px] shadow-2xl border border-slate-200/50">
            <div class="text-center mb-10">
                <img src="{{ \App\Support\Brand::logoUrl() }}" alt="{{ \App\Support\Brand::name() }}" class="h-20 w-auto object-contain mx-auto">
                <h2 class="mt-6 text-2xl font-black text-slate-900">Reset Password</h2>
                <p class="mt-2 text-slate-500 font-medium">Enter your email to recover your account</p>
            </div>
            
            <div x-show="message" class="mb-6 p-4 bg-green-50 border border-green-200 text-green-600 rounded-2xl text-sm font-bold" x-text="message"></div>
            <div x-show="error" class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-2xl text-sm font-bold" x-text="error"></div>

            <form @submit.prevent="reset" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Email Address</label>
                    <input type="email" x-model="email" required placeholder="name@example.com" class="w-full px-6 py-4 rounded-2xl bg-white border border-slate-200 focus:border-brand-navy focus:ring-4 focus:ring-brand-navy/10 outline-none transition-all font-medium">
                </div>

                <button type="submit" :disabled="loading" class="w-full bg-brand-navy text-white font-black py-4 rounded-2xl transition-all hover:bg-brand-navy-soft active:scale-[0.98] shadow-xl shadow-brand-navy/30 flex items-center justify-center space-x-3 disabled:opacity-50">
                    <span x-show="!loading">Send Reset Link</span>
                    <span x-show="loading" class="flex items-center">
                        <svg class="animate-spin h-5 w-5 text-white mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Sending...
                    </span>
                </button>

                <div class="text-center">
                    <a href="/login" class="text-sm font-bold text-brand-navy hover:underline">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</div>
</x-layouts.public>
