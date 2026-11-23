<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_package_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('data_package_id')->constrained('data_packages')->cascadeOnDelete();
            $table->decimal('selling_price', 10, 2);
            $table->timestamps();

            $table->unique(['user_id', 'data_package_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_package_prices');
    }
};
