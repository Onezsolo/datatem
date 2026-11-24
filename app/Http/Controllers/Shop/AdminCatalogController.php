<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Shop\Models\Category;
use App\Shop\Models\Order;
use App\Shop\Models\Product;
use App\Shop\Services\InventoryService;
use App\Shop\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminCatalogController
{
    public function categories(): JsonResponse
    {
        return response()->json([
            'categories' => Category::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $this->validateCategory($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $category = Category::create($data);

        return response()->json(['category' => $category], 201);
    }

    public function updateCategory(Request $request, Category $category): JsonResponse
    {
        $data = $this->validateCategory($request, $category);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $category->update($data);

        return response()->json(['category' => $category->refresh()]);
    }

    public function destroyCategory(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Move or delete the products in this category first.',
            ]);
        }

        $category->delete();

        return response()->json(['deleted' => true]);
    }

    public function products(Request $request): JsonResponse
    {
        $products = Product::query()
            ->with(['category:id,name,slug', 'primaryImage'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(fn ($query) => $query->where('name', 'like', $term)->orWhere('sku', 'like', $term));
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->latest('id')
            ->paginate(20);

        return response()->json($products);
    }

    public function storeProduct(Request $request): JsonResponse
    {
        $data = $this->validateProduct($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $product = Product::create($data);

        return response()->json(['product' => $product->load('category')], 201);
    }

    public function updateProduct(Request $request, Product $product): JsonResponse
    {
        $data = $this->validateProduct($request, $product);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $product->update($data);

        return response()->json(['product' => $product->refresh()->load('category')]);
    }

    public function destroyProduct(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['deleted' => true]);
    }

    public function adjustStock(Request $request, Product $product, InventoryService $inventory): JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'between:-100000,100000', 'not_in:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $movement = $inventory->adjust(
            $product,
            null,
            (int) $data['quantity'],
            (int) $data['quantity'] > 0 ? 'restock' : 'correction',
            $data['reason'] ?? 'Admin stock adjustment',
            null,
            'admin:'.($request->user()?->id ?? 'system')
        );

        return response()->json([
            'movement' => $movement,
            'stock_quantity' => $product->fresh()->stock_quantity,
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->withCount('items')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('id')
            ->paginate(20);

        return response()->json($orders);
    }

    public function updateOrderStatus(Request $request, Order $order, OrderService $orders): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', Rule::in([
                'cancelled', 'processing', 'shipped', 'delivered', 'refunded',
            ])],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $order = $orders->transition($order, $data['status'], null, $data['note'] ?? null);

        return response()->json(['order' => $order->fresh()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCategory(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('shop_categories', 'slug')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:60'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:shop_categories,id'],
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('shop_products', 'slug')->ignore($product?->id)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'sku' => ['required', 'string', 'max:80', Rule::unique('shop_products', 'sku')->ignore($product?->id)],
            'stock_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'track_inventory' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $data['stock_quantity'] = $data['stock_quantity'] ?? 0;
        $data['track_inventory'] = $data['track_inventory'] ?? true;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['is_featured'] = $data['is_featured'] ?? false;

        return $data;
    }
}
