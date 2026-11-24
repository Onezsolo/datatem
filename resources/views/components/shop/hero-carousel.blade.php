@php
    $images = [
        ['src' => asset('assets/shop/shopping-1.jpg'), 'alt' => 'Groceries packed in a shopping bag'],
        ['src' => asset('assets/shop/shopping-2.jpg'), 'alt' => 'Fresh groceries in a shopping bag'],
        ['src' => asset('assets/shop/shopping-3.jpg'), 'alt' => 'Reusable cloth shopping bag'],
        ['src' => asset('assets/shop/shopping-4.jpg'), 'alt' => 'Shopping bag ready for delivery'],
    ];
@endphp

<section class="bg-brand-navy text-white">
    <div class="container-page grid gap-6 py-6 sm:py-9 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:gap-10">
        <div>
            <p class="inline-flex items-center gap-2 rounded-control border border-white/25 px-3 py-1.5 text-xs font-bold text-brand-yellow">
                <span class="inline-block h-2 w-2 rounded-control bg-brand-yellow" aria-hidden="true"></span>
                Shop + data in one account
            </p>

            <h1 class="mt-3 text-2xl font-extrabold leading-tight tracking-tight sm:text-3xl lg:text-4xl">
                Everyday essentials, delivered across Ghana.
            </h1>
            <p class="mt-2 max-w-xl text-sm leading-relaxed text-nav-muted">
                Phones, home, beauty and more. Pay from your wallet and track every order.
            </p>

            <div class="mt-4 flex flex-wrap gap-2">
                <x-ui.button href="{{ route('shop.index').'#categories' }}" size="md">Browse products</x-ui.button>
                @if (\App\Services\FeatureSettings::enabled('data'))
                    <x-ui.button href="{{ route('services.data') }}" variant="ghost" size="md">Buy data</x-ui.button>
                @endif
            </div>

            <p class="mt-3 text-xs text-nav-muted">
                Wallet payment. Delivery fees shown before payment.
                <a href="{{ route('orders.shop') }}" class="ms-1 font-bold text-brand-yellow underline underline-offset-4 hover:text-white">My shop orders</a>
            </p>
        </div>

        <div class="relative overflow-hidden rounded-card border border-brand-yellow bg-brand-navy-soft">
            <div class="shop-marquee flex w-max gap-3 p-3">
                @foreach ([...$images, ...$images] as $image)
                    <img
                        src="{{ $image['src'] }}"
                        alt="{{ $image['alt'] }}"
                        loading="lazy"
                        class="h-32 w-44 rounded-control object-cover opacity-90 sm:h-40 sm:w-56 lg:h-48 lg:w-64"
                    >
                @endforeach
            </div>
            <div class="pointer-events-none absolute inset-0 flex items-end justify-end p-3" aria-hidden="true">
                <span class="rounded-control bg-brand-navy/50 px-2 py-1 text-[0.5625rem] font-bold uppercase tracking-[0.22em] text-white/70">
                    {{ \App\Support\Brand::name() }}
                </span>
            </div>
        </div>
    </div>
</section>
