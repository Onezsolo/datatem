<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Shop\Models\Category;
use App\Shop\Models\Product;
use App\Shop\Models\ProductVariant;
use Illuminate\Database\Seeder;

class ShopCatalogSeeder extends Seeder
{
    /**
     * Seed the initial catalogue. The seeder is idempotent and safe to run more
     * than once; it never deletes existing records. Product images are not
     * seeded because the binary assets are uploaded by the merchant.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Phones', 'slug' => 'phones', 'icon' => 'phone', 'sort_order' => 1],
            ['name' => 'Laptops', 'slug' => 'laptops', 'icon' => 'laptop', 'sort_order' => 2],
            ['name' => 'Accessories', 'slug' => 'accessories', 'icon' => 'headphones', 'sort_order' => 3],
            ['name' => 'Fashion', 'slug' => 'fashion', 'icon' => 'shirt', 'sort_order' => 4],
            ['name' => 'Shoes', 'slug' => 'shoes', 'icon' => 'shoe', 'sort_order' => 5],
            ['name' => 'Bags', 'slug' => 'bags', 'icon' => 'bag', 'sort_order' => 6],
            ['name' => 'Watches', 'slug' => 'watches', 'icon' => 'watch', 'sort_order' => 7],
            ['name' => 'Home Appliances', 'slug' => 'home-appliances', 'icon' => 'appliance', 'sort_order' => 8],
            ['name' => 'Beauty', 'slug' => 'beauty', 'icon' => 'beauty', 'sort_order' => 9],
            ['name' => 'Sports', 'slug' => 'sports', 'icon' => 'ball', 'sort_order' => 10],
            ['name' => 'Toys', 'slug' => 'toys', 'icon' => 'teddy', 'sort_order' => 11],
            ['name' => 'Furniture', 'slug' => 'furniture', 'icon' => 'chair', 'sort_order' => 12],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category + ['is_active' => true]
            );
        }

        $products = [
            [
                'category' => 'phones',
                'name' => 'Samsung Galaxy A56',
                'slug' => 'samsung-galaxy-a56',
                'short_description' => '128 GB, 5G, 6.7 inch AMOLED smartphone',
                'description' => 'Everyday 5G smartphone with a 50 MP camera, long battery life and expandable storage.',
                'price' => 3499,
                'compare_at_price' => 4099,
                'sku' => 'DT-PH-A56',
                'stock_quantity' => 25,
                'is_featured' => true,
            ],
            [
                'category' => 'accessories',
                'name' => 'Wireless Earbuds Pro',
                'slug' => 'wireless-earbuds-pro',
                'short_description' => 'Noise cancelling earbuds with charging case',
                'description' => 'Bluetooth 5.3 earbuds with active noise cancellation and up to 24 hours of playback.',
                'price' => 399,
                'compare_at_price' => 499,
                'sku' => 'DT-AC-EARBUDS',
                'stock_quantity' => 40,
                'is_featured' => true,
            ],
            [
                'category' => 'watches',
                'name' => 'Smart Watch Series 10',
                'slug' => 'smart-watch-series-10',
                'short_description' => 'Fitness tracking, calls and heart-rate monitoring',
                'description' => 'AMOLED smart watch with sleep tracking, workout modes and a seven-day battery.',
                'price' => 599,
                'compare_at_price' => 699,
                'sku' => 'DT-WT-SW10',
                'stock_quantity' => 18,
                'is_featured' => true,
            ],
            [
                'category' => 'laptops',
                'name' => 'Lenovo IdeaPad Slim 3',
                'slug' => 'lenovo-ideapad-slim-3',
                'short_description' => '15.6 inch, 16 GB RAM, 512 GB SSD',
                'description' => 'Everyday laptop for study and office work with a full-size keyboard and fast SSD storage.',
                'price' => 5299,
                'compare_at_price' => 5999,
                'sku' => 'DT-LT-IDEAPAD',
                'stock_quantity' => 10,
                'is_featured' => true,
            ],
            [
                'category' => 'fashion',
                'name' => 'Everyday Cotton T-Shirt',
                'slug' => 'everyday-cotton-t-shirt',
                'short_description' => 'Breathable cotton tee in black',
                'description' => 'Regular-fit cotton t-shirt with a reinforced neckline. Available in three sizes.',
                'price' => 89,
                'compare_at_price' => 109,
                'sku' => 'DT-FS-TSHIRT',
                'stock_quantity' => 60,
            ],
            [
                'category' => 'shoes',
                'name' => 'Running Shoes',
                'slug' => 'running-shoes',
                'short_description' => 'Lightweight trainers with cushioned sole',
                'description' => 'Breathable running shoes with a shock-absorbing sole for daily training.',
                'price' => 459,
                'compare_at_price' => 529,
                'sku' => 'DT-SH-RUN',
                'stock_quantity' => 22,
            ],
            [
                'category' => 'bags',
                'name' => 'Leather Tote Bag',
                'slug' => 'leather-tote-bag',
                'short_description' => 'Structured tote with laptop sleeve',
                'description' => 'Full-grain leather tote with an interior laptop sleeve and detachable shoulder strap.',
                'price' => 349,
                'compare_at_price' => 399,
                'sku' => 'DT-BG-TOTE',
                'stock_quantity' => 14,
            ],
            [
                'category' => 'home-appliances',
                'name' => '1.7L Electric Kettle',
                'slug' => '17l-electric-kettle',
                'short_description' => 'Stainless steel kettle with auto shut-off',
                'description' => 'Fast-boil stainless steel kettle with boil-dry protection and a 360 degree base.',
                'price' => 249,
                'compare_at_price' => 299,
                'sku' => 'DT-HA-KETTLE',
                'stock_quantity' => 30,
                'is_featured' => true,
            ],
            [
                'category' => 'home-appliances',
                'name' => '600W Blender',
                'slug' => '600w-blender',
                'short_description' => '1.5 L jug with two speed settings',
                'description' => 'Compact blender for smoothies and sauces with a dishwasher-safe jug.',
                'price' => 319,
                'compare_at_price' => 399,
                'sku' => 'DT-HA-BLENDER',
                'stock_quantity' => 16,
            ],
            [
                'category' => 'home-appliances',
                'name' => 'Ceramic Soleplate Iron',
                'slug' => 'ceramic-soleplate-iron',
                'short_description' => 'Steam iron with ceramic soleplate',
                'description' => 'Lightweight steam iron with adjustable temperature and a ceramic soleplate.',
                'price' => 159,
                'compare_at_price' => 189,
                'sku' => 'DT-HA-IRON',
                'stock_quantity' => 35,
            ],
            [
                'category' => 'sports',
                'name' => 'Match Football',
                'slug' => 'match-football',
                'short_description' => 'Size 5 match ball',
                'description' => 'Durable size 5 football for training and competitive matches.',
                'price' => 129,
                'compare_at_price' => 159,
                'sku' => 'DT-SP-BALL',
                'stock_quantity' => 50,
            ],
            [
                'category' => 'beauty',
                'name' => 'Skincare Gift Set',
                'slug' => 'skincare-gift-set',
                'short_description' => 'Cleanser, serum and moisturiser',
                'description' => 'Three-step skincare set for daily cleansing and hydration.',
                'price' => 279,
                'compare_at_price' => 329,
                'sku' => 'DT-BT-SKINCARE',
                'stock_quantity' => 20,
            ],
            [
                'category' => 'toys',
                'name' => 'Teddy Bear',
                'slug' => 'teddy-bear',
                'short_description' => 'Soft plush teddy, 40 cm',
                'description' => 'Soft-touch plush teddy bear suitable for children aged three and above.',
                'price' => 149,
                'compare_at_price' => 179,
                'sku' => 'DT-TO-TEDDY',
                'stock_quantity' => 12,
            ],
            [
                'category' => 'furniture',
                'name' => 'Fabric Armchair',
                'slug' => 'fabric-armchair',
                'short_description' => 'Upholstered armchair with wooden legs',
                'description' => 'Compact fabric armchair for living rooms and reading corners.',
                'price' => 1899,
                'compare_at_price' => 2199,
                'sku' => 'DT-FU-ARMCHAIR',
                'stock_quantity' => 5,
            ],
        ];

        $categoryIds = Category::query()->pluck('id', 'slug');

        foreach ($products as $data) {
            $categoryId = $categoryIds[$data['category']] ?? null;
            unset($data['category']);

            if (! $categoryId) {
                continue;
            }

            Product::updateOrCreate(
                ['sku' => $data['sku']],
                $data + ['category_id' => $categoryId, 'is_active' => true]
            );
        }

        $this->seedVariants();
    }

    private function seedVariants(): void
    {
        $variants = [
            'DT-PH-A56' => [
                ['name' => '128 GB', 'sku' => 'DT-PH-A56-128', 'price' => 3499, 'stock_quantity' => 15],
                ['name' => '256 GB', 'sku' => 'DT-PH-A56-256', 'price' => 3999, 'stock_quantity' => 10],
            ],
            'DT-FS-TSHIRT' => [
                ['name' => 'Small', 'sku' => 'DT-FS-TSHIRT-S', 'price' => 89, 'stock_quantity' => 20],
                ['name' => 'Medium', 'sku' => 'DT-FS-TSHIRT-M', 'price' => 89, 'stock_quantity' => 20],
                ['name' => 'Large', 'sku' => 'DT-FS-TSHIRT-L', 'price' => 89, 'stock_quantity' => 20],
            ],
            'DT-SH-RUN' => [
                ['name' => 'Size 40', 'sku' => 'DT-SH-RUN-40', 'price' => 459, 'stock_quantity' => 8],
                ['name' => 'Size 41', 'sku' => 'DT-SH-RUN-41', 'price' => 459, 'stock_quantity' => 8],
                ['name' => 'Size 42', 'sku' => 'DT-SH-RUN-42', 'price' => 459, 'stock_quantity' => 6],
            ],
        ];

        foreach ($variants as $parentSku => $items) {
            $product = Product::query()->where('sku', $parentSku)->first();

            if (! $product) {
                continue;
            }

            foreach ($items as $item) {
                ProductVariant::updateOrCreate(
                    ['sku' => $item['sku']],
                    $item + [
                        'product_id' => $product->id,
                        'track_inventory' => true,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
