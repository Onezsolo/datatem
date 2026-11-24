<x-layouts.shop :title="$product->name" :description="$product->short_description">
    <div class="container-page py-8">
        <nav aria-label="Breadcrumb" class="text-sm text-muted">
            <ol class="flex flex-wrap items-center gap-2">
                <li><a href="{{ route('shop.index') }}" class="inline-flex items-center gap-1 hover:underline"><x-ui.icon name="home" :size="14" />Home</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('shop.categories.show', $product->category->slug) }}" class="inline-flex items-center gap-1 hover:underline"><x-ui.icon :name="$product->category->icon ?: 'grid'" :size="14" />{{ $product->category->name }}</a></li>
                <li aria-hidden="true">/</li>
                <li class="font-semibold text-brand-navy">{{ $product->name }}</li>
            </ol>
        </nav>

        <div class="mt-6 grid gap-8 lg:grid-cols-2">
            <div>
                @if ($product->images->isNotEmpty())
                    <div class="overflow-hidden rounded-card border border-line bg-surface-muted">
                        <img src="{{ asset('storage/'.$product->images->first()->path) }}" alt="{{ $product->images->first()->alt_text ?: $product->name }}" class="h-80 w-full object-cover sm:h-96">
                    </div>
                    @if ($product->images->count() > 1)
                        <div class="mt-3 grid grid-cols-4 gap-2">
                            @foreach ($product->images->skip(1)->take(4) as $image)
                                <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt_text ?: $product->name }}" class="h-20 w-full rounded-control border border-line object-cover">
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="flex h-80 flex-col items-center justify-center gap-3 rounded-card border border-line bg-surface-muted text-muted sm:h-96">
                        <x-ui.icon :name="$product->category->icon ?: 'bag'" :size="56" class="text-brand-navy/25" />
                        <span class="text-xs font-semibold uppercase tracking-wide">Image coming soon</span>
                    </div>
                @endif
            </div>

            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.badge variant="info">{{ $product->category->name }}</x-ui.badge>
                    @if ($product->has_discount)
                        <x-ui.badge variant="accent">-{{ $product->discount_percentage }}%</x-ui.badge>
                    @endif
                    @if ($product->isInStock())
                        <x-ui.badge variant="neutral">In stock</x-ui.badge>
                    @else
                        <x-ui.badge variant="danger">Out of stock</x-ui.badge>
                    @endif
                </div>

                <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-brand-navy">{{ $product->name }}</h1>
                <div class="mt-3 flex flex-wrap items-baseline gap-2">
                    <span class="text-2xl font-extrabold text-brand-navy">{{ config('store.currency') }} {{ number_format((float) $product->price, 2) }}</span>
                    @if ($product->compare_at_price !== null)
                        <span class="text-sm text-muted line-through">{{ number_format((float) $product->compare_at_price, 2) }}</span>
                    @endif
                </div>

                @if ($product->short_description)
                    <p class="mt-3 text-sm leading-relaxed text-muted">{{ $product->short_description }}</p>
                @endif

                <form action="{{ route('shop.cart.items.store') }}" method="POST" class="mt-6 rounded-card border border-line bg-surface p-4">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    @if ($product->variants->isNotEmpty())
                        <label for="variant" class="block text-xs font-bold uppercase tracking-wide text-muted">Option</label>
                        <select id="variant" name="variant_id" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink" required>
                            <option value="">Choose an option</option>
                            @foreach ($product->variants as $variant)
                                <option value="{{ $variant->id }}" @disabled(! $variant->isInStock())>
                                    {{ $variant->name }} - {{ config('store.currency') }} {{ number_format($variant->effective_price, 2) }}{{ $variant->isInStock() ? '' : ' (out of stock)' }}
                                </option>
                            @endforeach
                        </select>
                    @endif

                    <div class="mt-3">
                        <label for="quantity" class="block text-xs font-bold uppercase tracking-wide text-muted">Quantity</label>
                        <input id="quantity" name="quantity" type="number" min="1" max="99" value="1" class="mt-1 h-11 w-24 rounded-control border border-line px-3 text-sm text-ink">
                    </div>

                    <div class="mt-4">
                        <x-ui.button type="submit" size="lg" class="w-full" :disabled="! $product->isInStock()">
                            {{ ! $product->isInStock() ? 'Out of stock' : (auth()->check() ? 'Add to cart' : 'Sign in to buy') }}
                        </x-ui.button>
                    </div>
                    <p class="mt-2 text-xs text-muted">
                        Price and availability are confirmed again in your cart.
                        @guest
                            <a href="{{ route('login', ['redirect' => url()->current()]) }}" class="font-bold text-brand-navy underline underline-offset-4">Sign in</a> to add items and pay from your wallet.
                        @endguest
                    </p>
                </form>

                <dl class="mt-6 space-y-2 text-sm">
                    <div class="flex justify-between gap-4 border-b border-line py-2">
                        <dt class="text-muted">SKU</dt>
                        <dd class="font-semibold text-brand-navy">{{ $product->sku }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-line py-2">
                        <dt class="text-muted">Category</dt>
                        <dd><a href="{{ route('shop.categories.show', $product->category->slug) }}" class="font-semibold text-brand-navy hover:underline">{{ $product->category->name }}</a></dd>
                    </div>
                    <div class="flex justify-between gap-4 py-2">
                        <dt class="text-muted">Stock</dt>
                        <dd class="font-semibold text-brand-navy">{{ $product->track_inventory ? $product->stock_quantity.' available' : 'Available' }}</dd>
                    </div>
                </dl>

                @if ($product->description)
                    <div class="mt-6">
                        <h2 class="text-lg font-extrabold text-brand-navy">Description</h2>
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $product->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-12" aria-labelledby="related-title">
                <h2 id="related-title" class="text-2xl font-extrabold tracking-tight text-brand-navy">More in {{ $product->category->name }}</h2>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($related as $item)
                        <x-shop.product-card :product="$item" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.shop>
