@props(['product'])

@php
    $image = $product->primaryImage;
    $available = $product->isInStock();
@endphp

<article class="flex h-full flex-col overflow-hidden rounded-card border border-line bg-surface">
    <a href="{{ route('shop.products.show', $product->slug) }}" class="block">
        <div class="relative flex h-40 items-center justify-center bg-surface-muted">
            @if ($image)
                <img
                    src="{{ asset('storage/'.$image->path) }}"
                    alt="{{ $image->alt_text ?: $product->name }}"
                    class="h-full w-full object-cover"
                    loading="lazy"
                >
            @else
                <span class="flex flex-col items-center gap-2 text-muted">
                    <x-ui.icon :name="$product->category?->icon ?: 'bag'" :size="34" class="text-brand-navy/30" />
                    <span class="text-[0.625rem] font-semibold uppercase tracking-wide">Image coming soon</span>
                </span>
            @endif

            @if ($product->has_discount)
                <span class="absolute left-2 top-2">
                    <x-ui.badge variant="accent" size="sm">-{{ $product->discount_percentage }}%</x-ui.badge>
                </span>
            @endif
        </div>

        <div class="p-3">
            <h3 class="text-sm font-bold leading-snug text-brand-navy">{{ $product->name }}</h3>
            @if ($product->short_description)
                <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-muted">{{ $product->short_description }}</p>
            @endif
            <div class="mt-2 flex flex-wrap items-baseline gap-1.5">
                <span class="text-sm font-extrabold text-brand-navy">{{ config('store.currency') }} {{ number_format((float) $product->price, 2) }}</span>
                @if ($product->compare_at_price !== null)
                    <span class="text-xs text-muted line-through">{{ number_format((float) $product->compare_at_price, 2) }}</span>
                @endif
            </div>
        </div>
    </a>

    <div class="mt-auto p-3 pt-0">
        @if ($product->variants_count > 0)
            <x-ui.button :href="route('shop.products.show', $product->slug)" variant="outline" size="sm" class="w-full">
                Choose options
            </x-ui.button>
        @else
            <form action="{{ route('shop.cart.items.store') }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="1">
                <x-ui.button type="submit" size="sm" class="w-full" :disabled="! $available">
                    {{ $available ? 'Add to cart' : 'Out of stock' }}
                </x-ui.button>
            </form>
        @endif
    </div>
</article>
