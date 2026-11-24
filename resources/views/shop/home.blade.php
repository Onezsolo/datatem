<x-layouts.member title="Shop home" description="Shop home essentials, phones, appliances and more from {{ \App\Support\Brand::name() }} with guest checkout.">
    <x-shop.hero-carousel />

    <section id="categories" class="container-page py-10" aria-labelledby="categories-title">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 id="categories-title" class="text-2xl font-extrabold tracking-tight text-brand-navy">Shop by category</h2>
                <p class="mt-1 text-sm text-muted">Find what you need from your account.</p>
            </div>
        </div>

        @if ($categories->isEmpty())
            <div class="mt-6 rounded-card border border-line bg-surface-muted p-6 text-sm text-muted">
                The catalogue is being prepared. Please check back shortly.
            </div>
        @else
            <ul class="mt-6 grid grid-cols-4 gap-1.5 sm:grid-cols-5 sm:gap-2 lg:grid-cols-6">
                @foreach ($categories as $category)
                    <li>
                        <a href="{{ route('shop.categories.show', $category->slug) }}" class="flex h-full flex-col items-center rounded-card bg-surface-muted p-2 text-center hover:bg-line focus-visible:outline-brand-navy sm:p-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-control bg-surface text-brand-navy" aria-hidden="true">
                                <x-ui.icon :name="$category->icon ?: 'grid'" :size="20" />
                            </span>
                            <span class="mt-1.5 text-[0.625rem] font-bold leading-tight text-brand-navy sm:text-xs">{{ $category->name }}</span>
                            <span class="mt-0.5 hidden text-[0.6875rem] text-muted sm:block">{{ $category->products_count }} {{ \Illuminate\Support\Str::plural('item', $category->products_count) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($featured->isNotEmpty())
        <section class="container-page pb-10" aria-labelledby="delivery-promo-title">
            <div class="flex flex-col gap-4 rounded-card border border-brand-yellow bg-brand-navy p-5 text-white sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-control bg-brand-yellow text-brand-navy" aria-hidden="true">
                        <x-ui.icon name="truck" :size="26" />
                    </span>
                    <div>
                        <h2 id="delivery-promo-title" class="text-xl font-extrabold">Fast and reliable delivery</h2>
                        <p class="mt-1 text-sm text-nav-muted">Delivery fees are calculated by zone before payment.</p>
                    </div>
                </div>
                <x-ui.button href="#categories" size="sm">Shop now</x-ui.button>
            </div>
        </section>

        <section class="container-page pb-10" aria-labelledby="featured-title">
            <x-ui.carousel heading="Featured products" label="Featured products" description="Popular picks with current pricing.">
                @foreach ($featured as $product)
                    <li class="carousel-item w-44 shrink-0 sm:w-52">
                        <x-shop.product-card :product="$product" />
                    </li>
                @endforeach
            </x-ui.carousel>
        </section>
    @endif

    <section class="container-page pb-10" aria-labelledby="new-title">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 id="new-title" class="text-2xl font-extrabold tracking-tight text-brand-navy">New arrivals</h2>
                <p class="mt-1 text-sm text-muted">Recently added products.</p>
            </div>
            <a href="{{ route('shop.search') }}" class="text-sm font-bold text-brand-navy underline underline-offset-4">Browse all</a>
        </div>

        @if ($newArrivals->isEmpty())
            <div class="mt-6 rounded-card border border-line bg-surface-muted p-6 text-sm text-muted">
                No products are available yet.
            </div>
        @else
            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($newArrivals as $product)
                    <x-shop.product-card :product="$product" />
                @endforeach
            </div>
        @endif
    </section>

    @foreach ($railCategories as $category)
        @php $categoryProducts = $railProducts->get($category->id, collect())->take(8); @endphp
        @if ($categoryProducts->isNotEmpty())
            <section class="container-page pb-10" aria-labelledby="rail-{{ $category->id }}">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <h2 id="rail-{{ $category->id }}" class="text-2xl font-extrabold tracking-tight text-brand-navy">{{ $category->name }}</h2>
                    <a href="{{ route('shop.categories.show', $category->slug) }}" class="text-sm font-bold text-brand-navy underline underline-offset-4">View category</a>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($categoryProducts as $product)
                        <x-shop.product-card :product="$product" />
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach
</x-layouts.member>
