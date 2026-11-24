@php
    $supportEmail = \App\Services\SettingsService::get('contact_email', '');
    $supportPhone = \App\Services\SettingsService::get('contact_phone', '');
@endphp

<footer class="mt-8 border-t-[3px] border-brand-yellow bg-brand-navy text-white sm:mt-12">
    <div class="container-page py-8 sm:py-10">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr]">
            <div>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 focus-visible:outline-brand-yellow" aria-label="{{ \App\Support\Brand::name() }} home">
                    <img src="{{ \App\Support\Brand::logoUrl() }}" alt="" width="48" height="48" class="h-10 w-10 rounded-control bg-surface object-contain p-1">
                    <span class="leading-none">
                        <span class="block text-base font-extrabold tracking-[0.06em] text-white">{{ \Illuminate\Support\Str::upper(\App\Support\Brand::name()) }}</span>
                        <span class="mt-1 block text-[0.625rem] font-bold tracking-[0.26em] text-brand-yellow">DATA &amp; SHOPPING</span>
                    </span>
                </a>
                <p class="mt-3 max-w-sm text-sm leading-relaxed text-nav-muted">
                    Mobile data bundles and everyday shopping for Ghana. Clear prices and instant delivery.
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <x-ui.badge variant="accent" size="sm">Paystack payments</x-ui.badge>
                    <span class="inline-flex min-h-6 items-center rounded-control border border-white/30 px-2 text-xs font-bold text-white">GHS pricing</span>
                </div>
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-brand-yellow">Shop</p>
                <ul class="mt-2 space-y-1.5 text-sm text-nav-muted">
                    <li><a class="hover:text-white" href="{{ route('bundles') }}">Data bundles</a></li>
                    <li><a class="hover:text-white" href="{{ url('/shop') }}">Products</a></li>
                    <li><a class="hover:text-white" href="{{ route('store.landing') }}">Reseller stores</a></li>
                    <li><a class="hover:text-white" href="{{ route('support') }}">Support</a></li>
                </ul>
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-wide text-brand-yellow">Information</p>
                <ul class="mt-2 space-y-1.5 text-sm">
                    <li><a class="text-nav-muted hover:text-white" href="{{ route('legal.privacy') }}">Privacy</a></li>
                    <li><a class="text-nav-muted hover:text-white" href="{{ route('legal.terms') }}">Terms of sale</a></li>
                    @if ($supportEmail !== '')
                        <li><a class="break-all text-nav-muted hover:text-white" href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a></li>
                    @endif
                    @if ($supportPhone !== '')
                        <li><a class="text-nav-muted hover:text-white" href="tel:{{ preg_replace('/\s+/', '', $supportPhone) }}">{{ $supportPhone }}</a></li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
    <div class="border-t border-white/15">
        <div class="container-page py-3 text-xs text-nav-muted">
            {{ now()->year }} {{ \App\Support\Brand::name() }}. All prices in GHS.
        </div>
    </div>
</footer>
