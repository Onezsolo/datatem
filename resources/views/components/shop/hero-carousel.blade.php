@php
    $slides = [
        [
            'eyebrow' => 'Shop + data in one account',
            'title' => 'Everyday essentials, delivered across Ghana.',
            'body' => 'Browse phones, home, beauty and more. Pay with Paystack and track every order from the same account as your data bundles.',
            'primary' => ['label' => 'Browse products', 'url' => route('shop.index').'#categories'],
            'secondary' => ['label' => 'Buy data', 'url' => route('services.data')],
        ],
    ];
@endphp

<section class="bg-brand-navy text-white">
    <div class="container-page grid gap-8 py-10 sm:py-14 lg:grid-cols-[1.2fr_1fr] lg:items-center">
        <div>
            <p class="inline-flex items-center gap-2 rounded-control border border-white/25 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-brand-yellow">
                <span class="inline-block h-2 w-2 rounded-control bg-brand-yellow" aria-hidden="true"></span>
                {{ $slides[0]['eyebrow'] }}
            </p>

            <h1 class="mt-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl lg:text-5xl">
                {{ $slides[0]['title'] }}
            </h1>

            <p class="mt-3 max-w-xl text-base leading-relaxed text-nav-muted">
                {{ $slides[0]['body'] }}
            </p>

            <div class="mt-6 flex flex-wrap gap-3">
                <x-ui.button :href="$slides[0]['primary']['url']" size="lg">{{ $slides[0]['primary']['label'] }}</x-ui.button>
                @if (\App\Services\FeatureSettings::enabled('data'))
                    <x-ui.button :href="$slides[0]['secondary']['url']" variant="ghost" size="lg">{{ $slides[0]['secondary']['label'] }}</x-ui.button>
                @endif
            </div>
        </div>

        <div class="rounded-card border border-brand-yellow bg-brand-navy-soft p-5">
            <h2 class="text-sm font-bold uppercase tracking-wide text-brand-yellow">Why shop here</h2>
            <ul class="mt-3 space-y-2 text-sm text-nav-muted">
                <li class="flex items-start gap-2">
                    <x-ui.icon name="check" :size="16" class="mt-0.5 text-brand-yellow" />
                    One account for every order
                </li>
                <li class="flex items-start gap-2">
                    <x-ui.icon name="check" :size="16" class="mt-0.5 text-brand-yellow" />
                    Delivery fees shown before you pay
                </li>
                <li class="flex items-start gap-2">
                    <x-ui.icon name="check" :size="16" class="mt-0.5 text-brand-yellow" />
                    Pay from your wallet after a quick top-up
                </li>
            </ul>
            <div class="mt-4 border-t border-white/15 pt-4">
                <a href="{{ route('orders.shop') }}" class="text-sm font-bold text-brand-yellow underline underline-offset-4 hover:text-white">My shop orders</a>
            </div>
        </div>
    </div>
</section>
