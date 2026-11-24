<x-layouts.app title="Design system" description="Datatem design tokens and UI primitives.">
    <x-ui.header :navigation="[
        ['label' => 'Home', 'url' => route('home'), 'current' => true],
        ['label' => 'Shop', 'url' => url('/shop')],
        ['label' => 'Bundles', 'url' => route('services.data')],
        ['label' => 'Support', 'url' => route('support')],
    ]" :cart-count="2" :cart-url="url('/cart')" />

    <main id="main-content" class="container-page py-10">
        <h1 class="text-3xl font-extrabold tracking-tight text-brand-navy">Design system</h1>
        <p class="mt-2 max-w-2xl text-sm text-muted">Datatem tokens and primitives. Preview only, never production data.</p>

        <section class="mt-8" aria-labelledby="tokens-heading">
            <h2 id="tokens-heading" class="text-xl font-extrabold text-brand-navy">Colour tokens</h2>
            <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ([
                    ['Brand navy', 'bg-brand-navy text-white'],
                    ['Brand yellow', 'bg-brand-yellow text-brand-navy'],
                    ['Brand red', 'bg-brand-red text-white'],
                    ['Red strong', 'bg-brand-red-strong text-white'],
                    ['Surface muted', 'bg-surface-muted text-brand-navy'],
                    ['Line', 'bg-line text-brand-navy'],
                ] as [$label, $classes])
                    <li class="{{ $classes }} rounded-card border border-line p-4 text-sm font-bold">{{ $label }}</li>
                @endforeach
            </ul>
        </section>

        <section class="mt-10" aria-labelledby="buttons-heading">
            <h2 id="buttons-heading" class="text-xl font-extrabold text-brand-navy">Buttons</h2>
            <div class="mt-4 flex flex-wrap items-center gap-3 rounded-card border border-line bg-surface p-5">
                <x-ui.button>Primary</x-ui.button>
                <x-ui.button variant="secondary">Secondary</x-ui.button>
                <x-ui.button variant="danger">Danger</x-ui.button>
                <x-ui.button variant="outline">Outline</x-ui.button>
                <x-ui.button variant="link">Link</x-ui.button>
                <x-ui.button variant="primary" size="lg">Large primary</x-ui.button>
                <x-ui.button variant="primary" disabled>Disabled</x-ui.button>
            </div>
        </section>

        <section class="mt-10" aria-labelledby="badges-heading">
            <h2 id="badges-heading" class="text-xl font-extrabold text-brand-navy">Badges</h2>
            <div class="mt-4 flex flex-wrap items-center gap-3 rounded-card border border-line bg-surface p-5">
                <x-ui.badge>Neutral</x-ui.badge>
                <x-ui.badge variant="accent">Accent</x-ui.badge>
                <x-ui.badge variant="danger">2 items</x-ui.badge>
                <x-ui.badge variant="info">Info</x-ui.badge>
                <x-ui.badge variant="neutral" size="sm">Small</x-ui.badge>
            </div>
        </section>

        <section class="mt-10" aria-labelledby="cards-heading">
            <h2 id="cards-heading" class="text-xl font-extrabold text-brand-navy">Cards</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <x-ui.card>
                    <h3 class="text-sm font-bold text-brand-navy">Default card</h3>
                    <p class="mt-2 text-sm text-muted">Border, white surface, 12px radius.</p>
                </x-ui.card>
                <x-ui.card variant="muted">
                    <h3 class="text-sm font-bold text-brand-navy">Muted card</h3>
                    <p class="mt-2 text-sm text-muted">Soft-grey surface for secondary panels.</p>
                </x-ui.card>
                <x-ui.card variant="feature">
                    <h3 class="text-sm font-bold text-white">Feature card</h3>
                    <p class="mt-2 text-sm text-nav-muted">Navy surface with yellow border.</p>
                </x-ui.card>
            </div>
        </section>

        <section class="mt-10" aria-labelledby="carousel-heading">
            <h2 id="carousel-heading" class="text-xl font-extrabold text-brand-navy">Carousel</h2>
            <div class="mt-4 rounded-card border border-line bg-surface p-5">
                <x-ui.carousel label="Example items" heading="Featured">
                    @foreach (range(1, 5) as $i)
                        <li class="carousel-item w-44 shrink-0">
                            <x-ui.card variant="product" padding="sm">
                                <div class="flex h-28 items-center justify-center rounded-control bg-surface-muted text-sm font-bold text-brand-navy">
                                    Item {{ $i }}
                                </div>
                                <p class="mt-3 text-sm font-bold text-brand-navy">Preview item</p>
                                <p class="mt-1 text-xs text-muted">GHS 25.00</p>
                            </x-ui.card>
                        </li>
                    @endforeach
                </x-ui.carousel>
            </div>
        </section>
    </main>
</x-layouts.app>
