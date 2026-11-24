<x-layouts.public title="Reseller stores" description="Visit a reseller store and buy data bundles without an account.">
    <div class="bg-surface-muted py-10 sm:py-14">
        <div class="container-page">
            <div class="mx-auto max-w-lg">
                <div class="rounded-card border border-line bg-surface p-6 sm:p-8" x-data="storeLanding()">
                    <p class="text-xs font-bold uppercase tracking-wide text-muted">Reseller stores</p>
                    <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-brand-navy">Visit a store</h1>
                    <p class="mt-2 text-sm text-muted">Enter the store code your reseller shared with you. No account needed.</p>

                    <form @submit.prevent="go()" class="mt-5">
                        <label for="store-code" class="block text-sm font-bold text-brand-navy">Store code</label>
                        <p class="mt-1 text-xs text-muted">From a link like <span class="font-semibold text-brand-navy">/store/iksoft</span>, enter the part after /store/.</p>
                        <input
                            id="store-code"
                            type="text"
                            x-model="storeCode"
                            placeholder="e.g. r5"
                            class="mt-2 h-11 w-full rounded-control border border-line px-3 text-sm font-semibold text-ink placeholder:text-muted"
                        >
                        <p x-show="errorMsg" x-cloak class="mt-2 text-sm font-bold text-brand-red-strong" x-text="errorMsg"></p>
                        <button type="submit" class="mt-5 min-h-11 w-full rounded-control bg-brand-yellow px-4 text-sm font-bold text-brand-navy hover:bg-brand-yellow-strong">Go to store</button>
                    </form>
                </div>

                <div class="mt-4 rounded-card border border-line bg-surface p-5">
                    <h2 class="text-sm font-bold text-brand-navy">Own a store?</h2>
                    <p class="mt-1 text-sm text-muted">Reseller stores sell data bundles with your own name and WhatsApp contact.</p>
                    <a href="{{ route('reseller') }}" class="mt-2 inline-block text-sm font-bold text-brand-navy underline underline-offset-4">Open reseller settings</a>
                </div>
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
</x-layouts.public>
