@php
    $authed = auth()->check();
@endphp

<x-layouts.public
    title="Data bundles and online shopping in Ghana"
    description="Buy mobile data bundles for MTN, AirtelTigo and Telecel, shop everyday products and pay from your wallet. Instant delivery across Ghana."
    :canonical="url('/')"
>
    <section class="bg-brand-navy text-white">
        <div class="container-page grid gap-10 py-12 sm:py-16 {{ $authed ? 'lg:grid-cols-[1.15fr_1fr] lg:items-center' : '' }}">
            <div class="{{ $authed ? '' : 'max-w-3xl' }}">
                @auth
                    @if (\App\Services\FeatureSettings::enabled('data'))
                        <p class="inline-flex items-center gap-2 rounded-control border border-white/25 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-brand-yellow">
                            <span class="inline-block h-2 w-2 rounded-control bg-brand-yellow" aria-hidden="true"></span>
                            MTN &middot; AirtelTigo &middot; Telecel
                        </p>
                    @endif

                    <h1 class="mt-5 text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
                        @if (\App\Services\FeatureSettings::enabled('data'))
                            Data in seconds.
                            <span class="block text-brand-yellow">Shopping made simple.</span>
                        @else
                            Everything you need.
                            <span class="block text-brand-yellow">Delivered across Ghana.</span>
                        @endif
                    </h1>

                    <p class="mt-4 max-w-xl text-base leading-relaxed text-nav-muted">
                        @if (\App\Services\FeatureSettings::enabled('data'))
                            Buy mobile data bundles and everyday products in one place. Top up your wallet, pay from it and track every order.
                        @else
                            Browse the Super Mall, add to cart and pay from your wallet. One account and one order history.
                        @endif
                    </p>

                    <div class="mt-7 flex flex-wrap gap-3">
                        @if (\App\Services\FeatureSettings::enabled('data'))
                            <x-ui.button href="{{ route('services.data') }}" size="lg">Browse bundles</x-ui.button>
                        @endif
                        @if (\App\Services\FeatureSettings::enabled('shop'))
                            <x-ui.button href="{{ url('/shop') }}" variant="{{ \App\Services\FeatureSettings::enabled('data') ? 'ghost' : 'primary' }}" size="lg">Visit Super Mall</x-ui.button>
                        @endif
                    </div>
                @else
                    <p class="inline-flex items-center gap-2 rounded-control border border-white/25 px-3 py-1.5 text-xs font-bold uppercase tracking-wide text-brand-yellow">
                        <span class="inline-block h-2 w-2 rounded-control bg-brand-yellow" aria-hidden="true"></span>
                        Your account
                    </p>

                    <h1 class="mt-5 text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
                        Sign in to continue.
                    </h1>

                    <p class="mt-4 max-w-xl text-base leading-relaxed text-nav-muted">
                        Access your wallet, buy data bundles, shop everyday products and track every order in one place.
                    </p>

                    <div class="mt-7 flex flex-wrap gap-3">
                        <x-ui.button href="{{ route('signup') }}" size="lg">Create free account</x-ui.button>
                        <x-ui.button href="{{ route('login') }}" variant="ghost" size="lg">Sign in</x-ui.button>
                    </div>
                @endauth

                <dl class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach ([['Instant', 'Delivery'], ['40%', 'Average savings'], ['100%', 'Guaranteed'], ['24/7', 'Support']] as [$value, $label])
                        <div class="border-l-2 border-brand-yellow pl-3">
                            <dt class="text-xl font-extrabold text-white">{{ $value }}</dt>
                            <dd class="mt-0.5 text-xs font-semibold text-nav-muted">{{ $label }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            @if ($authed && \App\Services\FeatureSettings::enabled('data'))
            <x-ui.card variant="feature" padding="lg" class="text-white">
                <h2 class="text-lg font-extrabold">Buy data now</h2>
                <p class="mt-1 text-sm text-nav-muted">Choose your network and get your bundle instantly.</p>

                <ul class="mt-5 space-y-2">
                    @foreach ([['MTN', 'assets/mtn.jpg', 'network=mtn'], ['AirtelTigo', 'assets/airtelTigo.png', 'network=at'], ['Telecel', 'assets/telecel.png', 'network=telecel']] as [$network, $logo, $query])
                        <li>
                            <a href="{{ route('services.data').'?'.$query }}" class="flex min-h-14 items-center gap-3 rounded-control border border-white/15 bg-white/5 px-3 hover:bg-white/10 focus-visible:outline-brand-yellow">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-control bg-surface">
                                    <img src="{{ asset($logo) }}" alt="{{ $network }}" class="h-8 w-8 object-contain">
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-bold text-white">{{ $network }}</span>
                                    <span class="block text-xs text-nav-muted">Data bundles</span>
                                </span>
                                <x-ui.icon name="chevron-right" :size="18" class="text-brand-yellow" />
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-5 border-t border-white/15 pt-4">
                    <a href="{{ route('topup') }}" class="text-sm font-bold text-brand-yellow underline underline-offset-4 hover:text-white">Top up your wallet</a>
                </div>
            </x-ui.card>
            @endif
        </div>
    </section>

    @auth
    <section class="container-page py-10 sm:py-12" aria-labelledby="quick-actions-heading">
        <h2 id="quick-actions-heading" class="text-xl font-extrabold tracking-tight text-brand-navy">Start here</h2>
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ([
                ['Buy data', 'Pick a bundle for your network', route('services.data'), 'phone'],
                ['Top up wallet', 'Pay with Paystack or manual transfer', route('topup'), 'cart'],
                ['Super Mall', 'Everyday items delivered to you', url('/shop'), 'bag'],
            ] as [$label, $description, $href, $icon])
                <x-ui.card :href="$href" padding="sm" class="h-full hover:bg-surface-muted">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-control bg-brand-navy text-brand-yellow">
                        <x-ui.icon :name="$icon" :size="18" />
                    </span>
                    <span class="mt-3 block text-sm font-bold text-brand-navy">{{ $label }}</span>
                    <span class="mt-1 block text-xs leading-relaxed text-muted">{{ $description }}</span>
                </x-ui.card>
            @endforeach
        </div>
    </section>

    <section class="border-y border-line bg-surface-muted" aria-labelledby="how-heading">
        <div class="container-page py-10 sm:py-12">
            <h2 id="how-heading" class="text-xl font-extrabold tracking-tight text-brand-navy">How it works</h2>
            <ol class="mt-5 grid gap-4 sm:grid-cols-3">
                @foreach ([
                    ['Choose', 'Pick a data bundle or a product from the shop.'],
                    ['Pay', 'Pay securely with Paystack or from your wallet.'],
                    ['Receive', 'Data is delivered instantly; products ship to your address.'],
                ] as $index => [$title, $description])
                    <li class="rounded-card border border-line bg-surface p-5">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-control bg-brand-yellow text-sm font-extrabold text-brand-navy">{{ $index + 1 }}</span>
                        <h3 class="mt-3 text-sm font-bold text-brand-navy">{{ $title }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-muted">{{ $description }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>
    @endauth

    @if ($authed && \App\Services\FeatureSettings::enabled('data'))
    <section class="container-page py-10 sm:py-12" aria-labelledby="networks-heading">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 id="networks-heading" class="text-xl font-extrabold tracking-tight text-brand-navy">Pick your network</h2>
                <p class="mt-1 text-sm text-muted">Affordable bundles for MTN, AirtelTigo and Telecel.</p>
            </div>
            <x-ui.button href="{{ route('services.data') }}" variant="outline" size="sm">See all bundles</x-ui.button>
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-3">
            @foreach ([
                ['MTN', 'assets/mtn.jpg', 'network=mtn', 'bg-mtn text-brand-navy'],
                ['AirtelTigo', 'assets/airtelTigo.png', 'network=at', 'bg-airteltigo text-white'],
                ['Telecel', 'assets/telecel.png', 'network=telecel', 'bg-telecel text-white'],
            ] as [$network, $logo, $query, $badge])
                <x-ui.card :href="route('services.data').'?'.$query" padding="sm" class="h-full hover:bg-surface-muted">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-control bg-surface-muted">
                            <img src="{{ asset($logo) }}" alt="{{ $network }}" class="h-9 w-9 object-contain">
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-brand-navy">{{ $network }}</p>
                            <span class="mt-1 inline-flex min-h-6 items-center rounded-control px-2 text-xs font-bold {{ $badge }}">Data bundles</span>
                        </div>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    </section>

    @endif

    @if ($authed && \App\Services\FeatureSettings::enabled('shop'))
    <section class="container-page pb-12" aria-labelledby="shop-heading">
        <div class="rounded-card border border-line bg-surface p-6 sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 id="shop-heading" class="text-xl font-extrabold tracking-tight text-brand-navy">Shopping and data in one account</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-relaxed text-muted">
                        The shop is live: sign in to browse products, add to cart and pay from your wallet. One account and one order history.
                    </p>
                </div>
                <x-ui.button href="{{ url('/shop') }}" variant="secondary">Open Super Mall</x-ui.button>
            </div>
        </div>
    </section>
    @endif

    <section class="border-t border-line bg-surface-muted" aria-labelledby="about-heading">
        <div class="container-page py-10 sm:py-12">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-brand-yellow-strong">About</p>
                    <h2 id="about-heading" class="mt-2 text-2xl font-extrabold text-brand-navy sm:text-3xl">Company information and updates.</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">A short look at who we are, how orders work and how payments are handled.</p>
                </div>
                <a href="{{ route('about.index') }}" class="text-sm font-bold text-brand-navy underline decoration-brand-yellow decoration-2 underline-offset-4">Read more</a>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                @foreach (collect(config('about.posts', []))->sortByDesc('date')->take(3) as $post)
                    <article class="flex flex-col rounded-card border border-line bg-surface p-5">
                        <p class="text-xs font-bold uppercase tracking-wide text-brand-yellow-strong">{{ $post['category'] }}</p>
                        <h3 class="mt-2 text-lg font-extrabold leading-snug text-brand-navy">
                            <a href="{{ route('about.show', $post['slug']) }}">{{ $post['title'] }}</a>
                        </h3>
                        <p class="mt-2 flex-1 text-sm leading-relaxed text-muted">{{ $post['excerpt'] }}</p>
                        <p class="mt-4 text-xs text-muted">
                            {{ \Illuminate\Support\Carbon::parse($post['date'])->format('j M Y') }} &middot; {{ $post['reading_time'] }} min read
                        </p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @auth
        <section class="bg-brand-yellow">
            <div class="container-page flex flex-wrap items-center justify-between gap-4 py-8">
                <div>
                    <h2 class="text-xl font-extrabold text-brand-navy">Welcome back</h2>
                    <p class="mt-1 text-sm font-semibold text-brand-navy">Your dashboard, data orders and shop are ready.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-ui.button href="{{ auth()->user()->role === 'admin' ? url('/admin/dashboard') : url('/dashboard') }}" variant="secondary">Dashboard</x-ui.button>
                    <x-ui.button href="{{ url('/shop') }}" variant="outline">Shop</x-ui.button>
                </div>
            </div>
        </section>
    @else
        <section class="bg-brand-yellow">
            <div class="container-page flex flex-wrap items-center justify-between gap-4 py-8">
                <div>
                    <h2 class="text-xl font-extrabold text-brand-navy">New here?</h2>
                    <p class="mt-1 text-sm font-semibold text-brand-navy">Create a free account and access your dashboard in under a minute.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <x-ui.button href="{{ route('signup') }}" variant="secondary">Create free account</x-ui.button>
                    <x-ui.button href="{{ route('login') }}" variant="outline">Sign in</x-ui.button>
                </div>
            </div>
        </section>
    @endauth
</x-layouts.public>
