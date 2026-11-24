<x-layouts.public title="Log in" robots="noindex,follow">
    @include('partials.auth-sync')
    <div
        class="bg-surface-muted py-10 sm:py-14"
        x-data="{
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
                        const redirectParam = new URLSearchParams(window.location.search).get('redirect');
                        const target = redirectParam && redirectParam.startsWith('/') && !redirectParam.startsWith('//')
                            ? redirectParam
                            : (data.user.role === 'developer' ? '/developer' : (data.user.role === 'admin' ? '/admin/dashboard' : '/dashboard'));
                        const welcomeUrl = new URL(target, window.location.origin);
                        welcomeUrl.searchParams.set('welcome', '1');
                        window.location.href = welcomeUrl.pathname + welcomeUrl.search + welcomeUrl.hash;
                    } else {
                        this.error = data.error || 'Invalid credentials';
                    }
                } catch (e) {
                    this.error = 'An error occurred. Please try again.';
                } finally {
                    this.loading = false;
                }
            }
        }"
    >
        <div class="mx-auto w-full max-w-md">
            <div class="rounded-card border border-line bg-surface p-6 sm:p-8">
                <div class="flex items-center gap-2.5">
                    <img src="{{ \App\Support\Brand::logoUrl() }}" alt="" width="48" height="48" class="h-10 w-10 rounded-control bg-surface object-contain p-1">
                    <span class="leading-none">
                        <span class="block text-sm font-extrabold tracking-[0.06em] text-brand-navy">{{ \Illuminate\Support\Str::upper(\App\Support\Brand::name()) }}</span>
                        <span class="mt-1 block text-[0.5rem] font-bold tracking-[0.24em] text-brand-red-strong">DATA &amp; SHOPPING</span>
                    </span>
                </div>

                <h1 class="mt-5 text-2xl font-extrabold tracking-tight text-brand-navy">Welcome back</h1>
                <p class="mt-1 text-sm text-muted">Log in to buy data and manage your orders.</p>

                <div x-show="error" x-cloak class="mt-4 rounded-card border border-brand-red-strong bg-surface p-3 text-sm font-semibold text-brand-navy" role="alert" x-text="error"></div>

                <form @submit.prevent="login" class="mt-5 space-y-4">
                    <div>
                        <label for="login" class="block text-sm font-bold text-brand-navy">Email or phone number</label>
                        <input id="login" name="email" type="text" x-model="login_field" required autocomplete="username" placeholder="024XXXXXXX or name@example.com" class="mt-1 h-11 w-full rounded-control border border-line bg-surface px-3 text-sm text-ink">
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <label for="password" class="block text-sm font-bold text-brand-navy">Password</label>
                            <a href="{{ route('password.request') }}" class="text-xs font-bold text-brand-navy underline underline-offset-4 hover:text-brand-red-strong">Forgot?</a>
                        </div>
                        <input id="password" name="password" type="password" x-model="password" required autocomplete="current-password" placeholder="Your password" class="mt-1 h-11 w-full rounded-control border border-line bg-surface px-3 text-sm text-ink">
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="keep-signed-in" type="checkbox" x-model="keep_me_signed_in" class="h-4 w-4 rounded-control border-line text-brand-navy">
                        <label for="keep-signed-in" class="text-sm font-medium text-muted">Keep me signed in</label>
                    </div>

                    <button type="submit" :disabled="loading" class="inline-flex min-h-11 w-full items-center justify-center rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong disabled:opacity-60">
                        <span x-show="!loading">Sign in</span>
                        <span x-show="loading" x-cloak>Logging in...</span>
                    </button>
                </form>

                <p class="mt-5 text-center text-sm text-muted">
                    New here?
                    <a href="{{ route('signup') }}" class="font-bold text-brand-navy underline underline-offset-4 hover:text-brand-red-strong">Create account</a>
                </p>
            </div>
        </div>
    </div>
</x-layouts.public>
