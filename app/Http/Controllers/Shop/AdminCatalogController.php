<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Shop\Models\Category;
use App\Shop\Models\Order;
use App\Shop\Models\Product;
use App\Shop\Models\ProductImage;
use App\Shop\Models\ProductVariant;
use App\Shop\Services\InventoryService;
use App\Shop\Services\OrderService;
use App\Shop\Services\ShopSettings;
use Illuminate\Support\Facades\Storage;
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
            ->with(['category:id,name,slug', 'primaryImage', 'images', 'variants'])
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

    public function uploadImage(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $path = $request->file('image')->store('product-images', 'public');
        $isPrimary = (bool) ($data['is_primary'] ?? false);

        if ($isPrimary || ! $product->images()->exists()) {
            $product->images()->update(['is_primary' => false]);
            $isPrimary = true;
        }

        $image = $product->images()->create([
            'path' => $path,
            'alt_text' => $data['alt_text'] ?? $product->name,
            'sort_order' => ((int) $product->images()->max('sort_order')) + 1,
            'is_primary' => $isPrimary,
        ]);

        return response()->json(['image' => $image], 201);
    }

    public function destroyImage(ProductImage $image): JsonResponse
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return response()->json(['deleted' => true]);
    }

    public function storeVariant(Request $request, Product $product): JsonResponse
    {
        $variant = $product->variants()->create($this->validateVariant($request));

        return response()->json(['variant' => $variant], 201);
    }

    public function updateVariant(Request $request, ProductVariant $variant): JsonResponse
    {
        $variant->update($this->validateVariant($request, $variant));

        return response()->json(['variant' => $variant->refresh()]);
    }

    public function destroyVariant(ProductVariant $variant): JsonResponse
    {
        $variant->delete();

        return response()->json(['deleted' => true]);
    }

    public function deliverySettings(): JsonResponse
    {
        return response()->json([
            'zones' => ShopSettings::deliveryZones(),
            'default_zone' => ShopSettings::defaultZone(),
            'service_charge' => ShopSettings::serviceCharge(),
        ]);
    }

    public function updateDeliverySettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'zones' => ['required', 'array', 'min:1'],
            'zones.*.label' => ['required', 'string', 'max:120'],
            'zones.*.fee' => ['required', 'numeric', 'min:0', 'max:10000'],
            'default_zone' => ['required', 'string'],
            'service_charge' => ['nullable', 'numeric', 'min:0', 'max:10000'],
        ]);

        ShopSettings::saveDelivery($data['zones'], (string) $data['default_zone'], (float) ($data['service_charge'] ?? 0));

        return $this->deliverySettings();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateVariant(Request $request, ?ProductVariant $variant = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['required', 'string', 'max:80', Rule::unique('shop_product_variants', 'sku')->ignore($variant?->id)],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'stock_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'track_inventory' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $data['stock_quantity'] = $data['stock_quantity'] ?? 0;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
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
