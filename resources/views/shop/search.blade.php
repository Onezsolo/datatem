<x-layouts.public title="Search" description="Search the {{ \App\Support\Brand::name() }} catalogue.">
    <div class="container-page py-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-brand-navy">Search products</h1>
        <p class="mt-2 text-sm text-muted">Search by product name, SKU or description.</p>

        <form method="GET" action="{{ route('shop.search') }}" class="mt-5 flex flex-wrap items-end gap-3 rounded-card border border-line bg-surface p-4" role="search">
            <div class="min-w-0 flex-1">
                <label for="search-term" class="block text-xs font-bold uppercase tracking-wide text-muted">Keyword</label>
                <input id="search-term" name="q" type="search" value="{{ $term }}" placeholder="Kettle, blender, phone" class="mt-1 h-11 w-full rounded-control border border-line px-3 text-sm text-ink">
            </div>
            <div>
                <label for="search-sort" class="block text-xs font-bold uppercase tracking-wide text-muted">Sort</label>
                <select id="search-sort" name="sort" class="mt-1 h-11 rounded-control border border-line px-3 text-sm text-ink">
                    <option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest</option>
                    <option value="price" @selected(request('sort') === 'price')>Price: low to high</option>
                    <option value="-price" @selected(request('sort') === '-price')>Price: high to low</option>
                    <option value="name" @selected(request('sort') === 'name')>Name</option>
                </select>
            </div>
            <x-ui.button type="submit">Search</x-ui.button>
        </form>

        @if ($term !== '')
            <p class="mt-6 text-sm text-muted">{{ $products->total() }} results for "{{ $term }}"</p>
        @endif

        @if ($term === '')
            <div class="mt-8 rounded-card border border-line bg-surface-muted p-8 text-sm text-muted">
                Enter a keyword to search the catalogue.
            </div>
        @elseif ($products->isEmpty())
            <div class="mt-6 rounded-card border border-line bg-surface-muted p-8 text-center">
                <p class="text-base font-bold text-brand-navy">No matching products</p>
                <p class="mt-1 text-sm text-muted">Try a shorter keyword or browse the categories from the home page.</p>
                <div class="mt-4"><x-ui.button href="{{ route('shop.index') }}" variant="outline">Browse categories</x-ui.button></div>
            </div>
        @else
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-shop.product-card :product="$product" />
                @endforeach
            </div>
            <div class="mt-8">{{ $products->links() }}</div>
        @endif
    </div>
</x-layouts.public>
