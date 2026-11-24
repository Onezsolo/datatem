<x-layouts.member :title="$category->name" :description="$category->description">
    <div class="container-page py-8">
        <nav aria-label="Breadcrumb" class="text-sm text-muted">
            <ol class="flex flex-wrap items-center gap-2">
                <li><a href="{{ route('shop.index') }}" class="inline-flex items-center gap-1 hover:underline"><x-ui.icon name="home" :size="14" />Home</a></li>
                <li aria-hidden="true">/</li>
                <li class="inline-flex items-center gap-1 font-semibold text-brand-navy"><x-ui.icon :name="$category->icon ?: 'grid'" :size="14" />{{ $category->name }}</li>
            </ol>
        </nav>

        <div class="mt-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-brand-navy">{{ $category->name }}</h1>
                @if ($category->description)
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-muted">{{ $category->description }}</p>
                @endif
            </div>
            <p class="text-sm text-muted">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}</p>
        </div>

        <form method="GET" action="{{ route('shop.categories.show', $category->slug) }}" class="mt-6 flex flex-wrap items-end gap-3 rounded-card border border-line bg-surface p-4">
            <div class="min-w-0 flex-1">
                <label for="category-search" class="block text-xs font-bold uppercase tracking-wide text-muted">Search this category</label>
                <input id="category-search" name="q" type="search" value="{{ $search }}" placeholder="Search products" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
            </div>
            <div>
                <label for="category-sort" class="block text-xs font-bold uppercase tracking-wide text-muted">Sort</label>
                <select id="category-sort" name="sort" class="mt-1 h-11 rounded-control border border-line px-3 text-sm text-ink">
                    <option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest</option>
                    <option value="price" @selected(request('sort') === 'price')>Price: low to high</option>
                    <option value="-price" @selected(request('sort') === '-price')>Price: high to low</option>
                    <option value="name" @selected(request('sort') === 'name')>Name</option>
                </select>
            </div>
            <x-ui.button type="submit">Apply</x-ui.button>
        </form>

        @if ($products->isEmpty())
            <div class="mt-8 rounded-card border border-line bg-surface-muted p-8 text-center">
                <p class="text-base font-bold text-brand-navy">No products found</p>
                <p class="mt-1 text-sm text-muted">Try a different search or browse another category.</p>
                <div class="mt-4">
                    <x-ui.button href="{{ route('shop.index') }}" variant="outline">Back to categories</x-ui.button>
                </div>
            </div>
        @else
            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-shop.product-card :product="$product" />
                @endforeach
            </div>

            <div class="mt-8">{{ $products->links() }}</div>
        @endif
    </div>
</x-layouts.member>
