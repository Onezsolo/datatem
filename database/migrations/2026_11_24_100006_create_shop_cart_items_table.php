<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('shop_carts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('shop_products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('shop_product_variants')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 10, 2);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['cart_id', 'product_id', 'variant_id']);
            $table->index('cart_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_cart_items');
    }
};
