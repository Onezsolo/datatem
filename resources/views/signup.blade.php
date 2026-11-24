<x-layouts.public title="Create account">
    @include('partials.auth-sync')
    <div
        class="bg-surface-muted py-10 sm:py-14"
        x-data="{
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
                        const redirectParam = new URLSearchParams(window.location.search).get('redirect');
                        const target = redirectParam && redirectParam.startsWith('/') && !redirectParam.startsWith('//')
                            ? redirectParam
                            : '/dashboard';
                        const welcomeUrl = new URL(target, window.location.origin);
                        welcomeUrl.searchParams.set('welcome', '1');
                        window.location.href = welcomeUrl.pathname + welcomeUrl.search + welcomeUrl.hash;
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

                <h1 class="mt-5 text-2xl font-extrabold tracking-tight text-brand-navy">Create your account</h1>
                <p class="mt-1 text-sm text-muted">Buy data and shop with one account.</p>

                <div x-show="Object.keys(errors).length > 0" x-cloak class="mt-4 rounded-card border border-brand-red-strong bg-surface p-3 text-sm font-semibold text-brand-navy" role="alert">
                    <template x-if="errors.error">
                        <p x-text="errors.error"></p>
                    </template>
                    <template x-if="!errors.error">
                        <ul class="list-inside list-disc space-y-0.5">
                            <template x-for="(messages, field) in errors" :key="field">
                                <template x-if="Array.isArray(messages)">
                                    <template x-for="message in messages" :key="message">
                                        <li x-text="message"></li>
                                    </template>
                                </template>
                            </template>
                        </ul>
                    </template>
                </div>

                <form @submit.prevent="signup" class="mt-5 space-y-4">
                    <div>
                        <label for="full-name" class="block text-sm font-bold text-brand-navy">Full name</label>
                        <input id="full-name" name="full_name" type="text" x-model="full_name" required autocomplete="name" placeholder="John Doe" class="mt-1 h-11 w-full rounded-control border border-line bg-surface px-3 text-sm text-ink">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-bold text-brand-navy">Email address</label>
                        <input id="email" name="email" type="email" x-model="email" required autocomplete="email" placeholder="name@example.com" class="mt-1 h-11 w-full rounded-control border border-line bg-surface px-3 text-sm text-ink">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-bold text-brand-navy">Phone number</label>
                        <input id="phone" name="phone" type="tel" x-model="phone" required autocomplete="tel" placeholder="024XXXXXXX" class="mt-1 h-11 w-full rounded-control border border-line bg-surface px-3 text-sm text-ink">
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="new-password" class="block text-sm font-bold text-brand-navy">Password</label>
                            <input id="new-password" name="password" type="password" x-model="password" required minlength="6" autocomplete="new-password" placeholder="Your password" class="mt-1 h-11 w-full rounded-control border border-line bg-surface px-3 text-sm text-ink">
                        </div>
                        <div>
                            <label for="confirm-password" class="block text-sm font-bold text-brand-navy">Confirm</label>
                            <input id="confirm-password" name="password_confirmation" type="password" x-model="password_confirmation" required autocomplete="new-password" placeholder="Repeat password" class="mt-1 h-11 w-full rounded-control border border-line bg-surface px-3 text-sm text-ink">
                        </div>
                    </div>

                    <div class="flex items-start gap-2">
                        <input id="terms" type="checkbox" required class="mt-1 h-4 w-4 rounded-control border-line text-brand-navy">
                        <label for="terms" class="text-sm font-medium text-muted">
                            I agree to the <a href="{{ route('legal.terms') }}" class="font-bold text-brand-navy underline underline-offset-4">Terms</a>
                            and <a href="{{ route('legal.privacy') }}" class="font-bold text-brand-navy underline underline-offset-4">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" :disabled="loading" class="inline-flex min-h-11 w-full items-center justify-center rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong disabled:opacity-60">
                        <span x-show="!loading">Create account</span>
                        <span x-show="loading" x-cloak>Creating account...</span>
                    </button>
                </form>

                <p class="mt-5 text-center text-sm text-muted">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-bold text-brand-navy underline underline-offset-4 hover:text-brand-red-strong">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</x-layouts.public>
