<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Shop\Models\Category;
use App\Shop\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CatalogController
{
    public function category(Category $category, Request $request): View
    {
        abort_unless($category->is_active, 404);

        $query = Product::query()
            ->where('category_id', $category->id)
            ->where('is_active', true)
            ->with(['category', 'primaryImage'])->withCount(['variants' => fn ($query) => $query->where('is_active', true)]);

        $this->applySearch($query, $request);
        $this->applySort($query, $request->string('sort')->toString());

        $products = $query->paginate(12)->withQueryString();

        return view('shop.category', [
            'category' => $category,
            'products' => $products,
            'search' => $request->string('q')->toString(),
        ]);
    }

    public function product(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category',
            'primaryImage',
            'images' => fn ($query) => $query->orderBy('sort_order'),
            'variants' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
        ]);

        $related = Product::query()
            ->where('category_id', $product->category_id)
            ->where('is_active', true)
            ->whereKeyNot($product->id)
            ->with(['category', 'primaryImage'])
            ->withCount(['variants' => fn ($query) => $query->where('is_active', true)])
            ->limit(4)
            ->get();

        return view('shop.product', compact('product', 'related'));
    }

    public function search(Request $request): View
    {
        $term = $request->string('q')->trim()->toString();

        $query = Product::query()
            ->where('is_active', true)
            ->with(['category', 'primaryImage'])->withCount(['variants' => fn ($query) => $query->where('is_active', true)]);

        $this->applySearch($query, $request);
        $this->applySort($query, $request->string('sort')->toString());

        $products = $term === ''
            ? $query->whereRaw('1 = 0')->paginate(12)
            : $query->paginate(12)->withQueryString();

        return view('shop.search', compact('products', 'term'));
    }

    private function applySearch(Builder $query, Request $request): void
    {
        $term = $request->string('q')->trim()->toString();

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        $query->where(function (Builder $query) use ($like): void {
            $query->where('name', 'like', $like)
                ->orWhere('sku', 'like', $like)
                ->orWhere('short_description', 'like', $like);
        });
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price' => $query->orderBy('price')->orderBy('id'),
            '-price' => $query->orderByDesc('price')->orderByDesc('id'),
            'name' => $query->orderBy('name')->orderBy('id'),
            'newest', '' => $query->orderByDesc('created_at')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };
    }
}
