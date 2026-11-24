<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Shop\Models\Category;
use App\Shop\Models\Product;
use Illuminate\Contracts\View\View;

class HomeController
{
    public function __invoke(): View
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->withCount(['products' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $featured = Product::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->with(['category', 'primaryImage'])->withCount(['variants' => fn ($query) => $query->where('is_active', true)])
            ->latest()
            ->limit(8)
            ->get();

        $newArrivals = Product::query()
            ->where('is_active', true)
            ->with(['category', 'primaryImage'])->withCount(['variants' => fn ($query) => $query->where('is_active', true)])
            ->latest()
            ->limit(8)
            ->get();

        $railCategoryIds = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(3)
            ->pluck('id');

        $railCategories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(3)
            ->get();

        $railProducts = Product::query()
            ->with(['category', 'primaryImage'])
            ->withCount(['variants' => fn ($query) => $query->where('is_active', true)])
            ->whereIn('category_id', $railCategoryIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->groupBy('category_id');

        return view('shop.home', compact('categories', 'featured', 'newArrivals', 'railCategories', 'railProducts'));
    }
}
